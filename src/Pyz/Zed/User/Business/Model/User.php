<?php

namespace Pyz\Zed\User\Business\Model;

use Generated\Shared\Transfer\UserTransfer;
use Orm\Zed\User\Persistence\SpyUser;
use Spryker\Zed\User\Business\Exception\PasswordEncryptionFailedException;

class User extends \Spryker\Zed\User\Business\Model\User
{
    /**
     * @param \Generated\Shared\Transfer\UserTransfer $userTransfer
     *
     * @throws \Spryker\Zed\User\Business\Exception\PasswordEncryptionFailedException
     *
     * @return \Generated\Shared\Transfer\UserTransfer
     */
    protected function executeSaveTransaction(UserTransfer $userTransfer): UserTransfer
    {
        if ($userTransfer->getIdUser() !== null) {
            $userEntity = $this->getEntityUserById($userTransfer->getIdUser());
        } else {
            $userEntity = new SpyUser();
        }

        $userTransfer = $this->executePreSavePlugins($userTransfer);
        $modifiedUser = $userTransfer->modifiedToArray();

        if (!$userEntity->isNew()) {
            unset($modifiedUser[UserTransfer::PASSWORD]);
        }

        $userEntity->fromArray($modifiedUser);

        $password = $userTransfer->getPassword();
        if ($password && $this->isRawPassword($password)) {
            $passwordEncrypted = $this->encryptPassword($password);
            if ($passwordEncrypted === false) {
                throw new PasswordEncryptionFailedException();
            }

            $userEntity->setPassword($passwordEncrypted);
        }

        $userEntity->save();
        $userTransfer = $this->entityToTransfer(
            $userEntity,
            (new UserTransfer())->fromArray($userTransfer->toArray()),
        );
        $userTransfer = $this->executePostSavePlugins($userTransfer);

        return $userTransfer;
    }

    /**
     * @param string $password
     *
     * @return bool
     */
    private function isRawPassword($password)
    {
        $passwordInfo = password_get_info($password);

        return $passwordInfo['algoName'] === 'unknown';
    }
}
