<?php

namespace Pyz\Zed\Acl;

use Spryker\Zed\AclEntity\Communication\Plugin\Acl\AclEntityAclRolePostSavePlugin;
use Spryker\Zed\AclEntity\Communication\Plugin\Acl\AclRulesAclRolesExpanderPlugin;
use Spryker\Zed\Acl\AclDependencyProvider as SprykerAclDependencyProvider;

class AclDependencyProvider extends SprykerAclDependencyProvider
{
    /**
     * @return \Spryker\Zed\AclExtension\Dependency\Plugin\AclRolesExpanderPluginInterface[]
     */
    protected function getAclRolesExpanderPlugins(): array
    {
        return [
            new AclRulesAclRolesExpanderPlugin(),
        ];
    }

    /**
     * @return \Spryker\Zed\AclExtension\Dependency\Plugin\AclRolePostSavePluginInterface[]
     */
    protected function getAclRolePostSavePlugins(): array
    {
        return [
            new AclEntityAclRolePostSavePlugin(),
        ];
    }
}
