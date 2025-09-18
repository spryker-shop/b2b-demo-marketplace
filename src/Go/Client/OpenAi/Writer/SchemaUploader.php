<?php

declare(strict_types=1);

namespace Go\Client\OpenAi\Writer;


use GuzzleHttp\Client as GuzzleHttpClient;
use RuntimeException;

class SchemaUploader
{
    protected const SCHEMA_PATH       = APPLICATION_ROOT_DIR . '/src/Go/Zed/GuiAssistant/chat_openapi.yaml';
    protected const VECTOR_STORE_KEY  = 'chat-app-openapi-schema';
    protected const VECTOR_STORE_NAME = 'Chat OpenAPI Schema Store';

    public function __construct(
        protected string $apiKey,
        protected string $model,
        protected string $timeout,
        protected GuzzleHttpClient $httpClient
    ) {}

    public function upload(): array
    {
        $filename = basename(self::SCHEMA_PATH);
        if (!is_file(self::SCHEMA_PATH)) {
            throw new RuntimeException("Schema file not found: " . self::SCHEMA_PATH);
        }

        // 1. Find or create vector store
        $vectorStoreId = $this->findVectorStore()
            ?? $this->createVectorStore();

        // 2. Check if file with same filename exists
        $existingFileId = $this->findFileInVectorStore($vectorStoreId, $this->castToVectorStoreFileName($filename));

        // 3. Upload fresh file
        $newFileId = $this->uploadFile(self::SCHEMA_PATH, $this->castToVectorStoreFileName($filename));

        // 4. Attach to vector store
        $this->attachFile($vectorStoreId, $newFileId);

        // 5. Poll until ready
        $this->pollVectorization($vectorStoreId, $newFileId);

        // 6. Detach old if replaced
        if ($existingFileId && $existingFileId !== $newFileId) {
            $this->detachFile($vectorStoreId, $existingFileId);
        }

        return ['vector_store_id' => $vectorStoreId, 'file_id' => $newFileId];
    }

    public function getDetails(): array
    {
        $filename = basename(self::SCHEMA_PATH);

        $vectorStoreId = $this->findVectorStore();
        $existingFileId = $this->findFileInVectorStore($vectorStoreId, $this->castToVectorStoreFileName($filename));

        return ['vector_store_id' => $vectorStoreId, 'file_id' => $existingFileId];
    }

    public function listVectorStoreFiles(): array
    {
        $vectorStoreId = $this->findVectorStore();

        $resp = $this->get("vector_stores/{$vectorStoreId}/files");
        $files = [];

        foreach ($resp['data'] ?? [] as $fileRef) {
            $fileDetails = $this->get("files/{$fileRef['id']}");
            $files[] = [
                'id' => $fileDetails['id'] ?? null,
                'filename' => $fileDetails['filename'] ?? null,
                'purpose' => $fileDetails['purpose'] ?? null,
                'status' => $fileRef['status'] ?? null,
                'created_at' => $fileDetails['created_at'] ?? null,
                'bytes' => $fileDetails['bytes'] ?? null,
            ];
        }

        return $files;
    }

    private function findVectorStore(): ?string
    {
        $response = $this->get('vector_stores');
        foreach ($response['data'] ?? [] as $vs) {
            if (($vs['metadata']['key'] ?? null) === self::VECTOR_STORE_KEY) {
                return $vs['id'];
            }
        }
        return null;
    }

    private function createVectorStore(): string
    {
        $resp = $this->post('vector_stores', [
            'name'     => self::VECTOR_STORE_NAME,
            'metadata' => ['key' => self::VECTOR_STORE_KEY],
        ]);

        return $resp['id'] ?? throw new RuntimeException('Failed to create vector store');
    }

    private function findFileInVectorStore(string $vectorStoreId, string $target): ?string
    {
        $resp = $this->get("vector_stores/{$vectorStoreId}/files");
        foreach ($resp['data'] ?? [] as $row) {
            $file = $this->get("files/{$row['id']}");
            if (($file['filename'] ?? null) === $target) {
                return $file['id'];
            }
        }

        return null;
    }

    private function uploadFile(string $uploadPath, string $destinationFileName): string
    {
        $response = $this->httpClient->post('https://api.openai.com/v1/files', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
            'multipart' => [
                ['name' => 'purpose', 'contents' => 'assistants'],
                ['name' => 'file', 'contents' => fopen($uploadPath, 'r'), 'filename' => $destinationFileName],
            ],
            'timeout' => $this->timeout,
        ]);

        $json = json_decode((string) $response->getBody(), true);
        return $json['id'] ?? throw new RuntimeException('File upload failed');
    }

    private function attachFile(string $vectorStoreId, string $fileId): void
    {
        $this->post("vector_stores/{$vectorStoreId}/files", ['file_id' => $fileId]);
    }

    private function detachFile(string $vectorStoreId, string $fileId): void
    {
        $this->delete("vector_stores/{$vectorStoreId}/files/{$fileId}");
    }

    private function pollVectorization(string $vectorStoreId, string $fileId): void
    {
        $deadline = time() + 90;
        do {
            $resp = $this->get("vector_stores/{$vectorStoreId}/files/{$fileId}");
            $status = $resp['status'] ?? null;
            if ($status === 'completed') return;
            if ($status === 'failed') throw new RuntimeException('Vectorization failed');
            sleep(2);
        } while (time() < $deadline);

        throw new RuntimeException('Timeout waiting for vectorization');
    }

    /* ---------- low-level wrappers ---------- */

    private function get(string $uri): array
    {
        $response = $this->httpClient->get("https://api.openai.com/v1/{$uri}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
            'timeout' => $this->timeout,
        ]);
        return json_decode((string) $response->getBody(), true);
    }

    private function post(string $uri, array $body): array
    {
        $response = $this->httpClient->post("https://api.openai.com/v1/{$uri}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
            'json'    => $body,
            'timeout' => $this->timeout,
        ]);
        return json_decode((string) $response->getBody(), true);
    }

    private function delete(string $uri): void
    {
        $this->httpClient->delete("https://api.openai.com/v1/{$uri}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
            'timeout' => $this->timeout,
        ]);
    }

    private function castToVectorStoreFileName(string $filename): string
    {
        return str_replace(['.yml', '.yaml'], '.txt', $filename);
    }
}
