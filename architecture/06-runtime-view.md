# Runtime View

Behavior and interactions of building blocks as runtime scenarios.

## Key Scenarios

### API Payment Flow

Shows the complete payment processing flow from checkout to order placement.

```mermaid
sequenceDiagram
    autonumber

    %% Group participants in colored boxes for easier identification
    box rgb(255, 242, 204) API Client
        participant APIClient as API Client (Storefront)
    end
    box rgb(201, 218, 248) Glue
        participant Glue as Glue
    end
    box rgb(226, 239, 218) Payment Services Provider
        participant PSP as Payment Services Provider<br/>(Third Party)
    end

    %% Begin actual sequence
    Note over APIClient: User submits checkout
    APIClient->>Glue: POST /checkout-data?include=fraud <br/>
    Glue->>Glue: Do external Fraud call
    Glue-->>APIClient: Fraud validation result
    APIClient->>Glue: POST /payments endpoint<br/>(send checkout data)
    Glue->>Glue: Initialize pre-payment
    Glue-->>APIClient: PaymentID + Redirect URL
    APIClient->>PSP: Redirect to payment provider + PaymentID

    PSP->>PSP: Process payment <br/> (incl. Authorize 3D)

    PSP-->>APIClient: Return payment<br/>status & payload

    APIClient->>Glue: PUT /payments/{paymentID}
    Glue->>Glue: Process payload, <br/> Update payment data

    APIClient-->>PSP: Payment saved
    PSP->>APIClient: Redirect to merchant

    APIClient->>Glue: Call /checkout endpoint<br/>(send checkout data)
    Glue->>Glue: Pre-check plugins:<br/>check if order is authorised
    Glue-xAPIClient: Result (Payment Failed)
    Glue->>Glue: Place order

    Glue->>APIClient: Result (Success)

    Note over APIClient: Show result to user
    APIClient->>APIClient: Display success/failure<br/>page
```

**Key Steps:**
1. Customer submits checkout with fraud validation
2. Payment is initialized, customer redirected to PSP
3. PSP processes payment including 3D Secure
4. Payment status returned and saved
5. Order is placed after authorization confirmation

### Publish and Synchronization

Shows how data changes propagate from database to Redis/ElasticSearch.

```mermaid
sequenceDiagram
    autonumber

    participant Admin as Admin / Import
    participant Zed as Zed (PHP)
    participant Jenkins as Zed (PHP) - Jenkins
    participant DB as Database
    participant RabbitMQ
    participant Redis as Redis,<br/>ElasticSearch

    Note over Admin,Redis: Publish and Synchronization Sequence Diagram

    %% Data Import Phase (Blue)
    rect rgb(201, 218, 248)
        Note right of Admin: Data importer / zed ui backend
        Admin->>Zed: update / import product
        activate Zed
        Zed->>DB: Save product
        Zed->>RabbitMQ: Trigger "event" message<br/>(entity updated/created/deleted)
        Zed-->>Admin: Response
        deactivate Zed
    end

    %% Search/Storage Publish Phase (Green)
    rect rgb(226, 239, 218)
        Note right of Jenkins: Search / Storage Publish listeners
        RabbitMQ->>Jenkins: "event" message gets consumed
        activate Jenkins
        Jenkins->>DB: Save "storage" / "search" data
        Jenkins->>RabbitMQ: Trigger "sync" message<br/>(contains corresp. product data)
        deactivate Jenkins
    end

    %% Synchronization Phase (Yellow)
    rect rgb(255, 242, 204)
        Note right of Jenkins: Synchronization module
        RabbitMQ->>Jenkins: "sync" message gets consumed
        activate Jenkins
        Jenkins->>Redis: Data from message gets delivered<br/>to Redis / Elastic search
        deactivate Jenkins
    end
```

**Key Steps:**
1. Admin/import updates product data
2. Event message published to RabbitMQ
3. Listeners transform and stage data
4. Sync message triggers cache updates
5. Data delivered to Redis and ElasticSearch

---

*Corresponds to [arc42 Section 6](https://docs.arc42.org/section-6/)*
