# TenantOnboarding Module

## Overview

The TenantOnboarding module provides a comprehensive self-service tenant registration and onboarding system for multi-tenant Spryker applications. It includes public registration forms, admin review interfaces, automated onboarding pipelines, and extensible step processing.

## Features

### ✅ Implemented Core Features

1. **Database Schema**
   - `pyz_tenant_registration` table with all required fields
   - Proper indexes and constraints
   - Integration with Propel ORM

2. **Transfer Objects**
   - Complete transfer definitions in `tenant_onboarding.transfer.xml`
   - Support for registration, validation, and processing workflows

3. **Business Logic Layer**
   - **Registration Submission**: Validation, password hashing, duplicate checking
   - **Registration Management**: Approval/decline workflow with status transitions
   - **Password Validation**: Configurable password policy enforcement
   - **Onboarding Processing**: Extensible pipeline with plugin system

4. **Communication Layer**
   - **Admin Interface**: Table view with approve/decline actions
   - **Public Registration Form**: Bootstrap-styled with real-time validation
   - **AJAX Endpoints**: Email/tenant name availability checking
   - **Controllers**: Proper separation of admin and public functionality

5. **Persistence Layer**
   - Repository pattern for data retrieval with criteria support
   - Entity manager for create/update operations
   - Availability checking for unique constraints

6. **Configuration & Extension Points**
   - Configurable password policies
   - Plugin-based onboarding steps
   - Queue configuration for async processing

## Project Structure

```
src/Pyz/Zed/TenantOnboarding/
├── Business/
│   ├── Plugin/
│   │   └── OnboardingStepPluginInterface.php
│   ├── Processor/
│   │   ├── OnboardingProcessor.php
│   │   └── OnboardingProcessorInterface.php
│   ├── Service/
│   │   ├── RegistrationAccepter.php
│   │   ├── RegistrationAccepterInterface.php
│   │   ├── RegistrationDecliner.php
│   │   ├── RegistrationDeclinerInterface.php
│   │   ├── RegistrationSubmitter.php
│   │   └── RegistrationSubmitterInterface.php
│   ├── Validator/
│   │   ├── PasswordValidator.php
│   │   ├── PasswordValidatorInterface.php
│   │   ├── RegistrationValidator.php
│   │   └── RegistrationValidatorInterface.php
│   ├── TenantOnboardingBusinessFactory.php
│   ├── TenantOnboardingFacade.php
│   └── TenantOnboardingFacadeInterface.php
├── Communication/
│   ├── Controller/
│   │   ├── IndexController.php (Admin)
│   │   └── RegistrationController.php (Public)
│   ├── Form/
│   │   └── TenantRegistrationForm.php
│   ├── Plugin/
│   │   ├── CreateBackofficeUserOnboardingStepPlugin.php
│   │   └── Queue/
│   │       └── TenantOnboardingQueueMessageProcessorPlugin.php
│   ├── Table/
│   │   └── TenantRegistrationTable.php
│   └── TenantOnboardingCommunicationFactory.php
├── Persistence/
│   ├── Propel/
│   │   └── Schema/
│   │       └── pyz_tenant_onboarding.schema.xml
│   ├── TenantOnboardingEntityManager.php
│   ├── TenantOnboardingEntityManagerInterface.php
│   ├── TenantOnboardingPersistenceFactory.php
│   ├── TenantOnboardingRepository.php
│   └── TenantOnboardingRepositoryInterface.php
├── Presentation/
│   ├── Index/
│   │   └── index.twig (Admin table view)
│   └── Registration/
│       ├── form.twig (Public registration)
│       └── success.twig (Success page)
├── TenantOnboardingConfig.php
└── TenantOnboardingDependencyProvider.php

src/Pyz/Shared/TenantOnboarding/
└── Transfer/
    └── tenant_onboarding.transfer.xml
```

## Usage Examples

### 1. Public Registration
```php
// Access the public registration form
GET /tenant-onboarding/registration/form

// Submit registration
POST /tenant-onboarding/registration/form
```

### 2. Admin Management
```php
// View pending registrations
GET /tenant-onboarding/index

// Approve registration
POST /tenant-onboarding/index/approve?id=123

// Decline registration  
POST /tenant-onboarding/index/decline?id=123&reason=...
```

### 3. Programmatic Usage
```php
// Submit registration
$registrationTransfer = new TenantRegistrationTransfer();
$registrationTransfer->setCompanyName('ACME Corp');
$registrationTransfer->setTenantName('acme-corp');
$registrationTransfer->setEmail('admin@acme.com');
$registrationTransfer->setPassword('SecurePass123!');

$response = $tenantOnboardingFacade->submitRegistration($registrationTransfer);

// Check availability
$isEmailAvailable = $tenantOnboardingFacade->isEmailAvailable('test@example.com');
$isTenantNameAvailable = $tenantOnboardingFacade->isTenantNameAvailable('my-tenant');
```

### 4. Custom Onboarding Steps
```php
class CustomOnboardingStepPlugin implements OnboardingStepPluginInterface
{
    public function execute(TenantRegistrationTransfer $tenantRegistrationTransfer): TenantOnboardingStepResultTransfer
    {
        $result = new TenantOnboardingStepResultTransfer();
        
        try {
            // Your custom onboarding logic here
            // e.g., create database, setup tenant config, etc.
            
            $result->setIsSuccessful(true);
            $result->setContext(['created_database' => true]);
        } catch (\Exception $e) {
            $result->setIsSuccessful(false);
            $result->setErrors([$e->getMessage()]);
        }
        
        return $result;
    }
    
    public function getName(): string
    {
        return 'CustomOnboardingStep';
    }
}
```

## Configuration

### Password Policy
```php
// TenantOnboardingConfig.php
public function getPasswordPolicy(): PasswordPolicyTransfer
{
    $policy = new PasswordPolicyTransfer();
    $policy->setMinLength(12);
    $policy->setRequireUpper(true);
    $policy->setRequireLower(true);
    $policy->setRequireNumber(true);
    $policy->setRequireSpecial(true);
    
    return $policy;
}
```

### Onboarding Steps
```php
// TenantOnboardingDependencyProvider.php
protected function getOnboardingStepPlugins(): array
{
    return [
        new CreateBackofficeUserOnboardingStepPlugin(),
        new SetupTenantDatabaseOnboardingStepPlugin(),
        new SendWelcomeEmailOnboardingStepPlugin(),
        // Add more steps as needed
    ];
}
```

## Status Workflow

1. **pending** - Initial registration status
2. **approved** - Admin approved, queued for processing
3. **processing** - Onboarding steps in progress
4. **completed** - Successfully onboarded
5. **declined** - Rejected by admin
6. **failed** - Onboarding process failed

## Integration Points

### Queue System
- Queue: `tenant-onboarding`
- Message processor: `TenantOnboardingQueueMessageProcessorPlugin`
- Async processing of onboarding steps

### Mail System  
- Decline notifications
- Welcome emails
- Status updates

### ACL Integration
- Admin access control
- Role-based permissions
- Menu integration

## Next Steps for Full Implementation

### 🔄 Immediate TODOs

1. **Generate Transfer Classes**
   ```bash
   docker/sdk console transfer:generate
   ```

2. **Add Menu Integration**
   - Create navigation plugin
   - Add to AdminMenuDependencyProvider

3. **Configure Queue Processing**
   - Register queue in project config
   - Add message processor to QueueDependencyProvider

4. **Setup Mail Templates**
   - Create decline notification template
   - Configure mail type mappings

5. **Add ACL Configuration**
   - Define access roles
   - Configure permissions

### 🚀 Enhanced Features

1. **Advanced Validation**
   - Domain verification
   - Business registration checks
   - Anti-fraud measures

2. **Multi-step Registration**
   - Document upload
   - Identity verification
   - Payment processing

3. **Analytics & Reporting**
   - Registration metrics
   - Conversion tracking
   - Admin dashboards

4. **API Integration**
   - REST endpoints
   - Webhook notifications
   - Third-party integrations

## Testing

### Unit Tests
```bash
vendor/bin/codecept run unit -c codeception.yml
```

### Integration Tests  
```bash
vendor/bin/codecept run functional -c codeception.functional.yml
```

### Browser Tests
```bash
vendor/bin/codecept run acceptance -c codeception.acceptance.yml
```

## Security Considerations

- ✅ Password hashing with Argon2ID
- ✅ CSRF protection on forms
- ✅ Input validation and sanitization  
- ✅ SQL injection prevention via Propel ORM
- ✅ XSS protection in templates
- ⚠️ Rate limiting (recommended)
- ⚠️ Email verification (recommended)
- ⚠️ Admin audit logging (recommended)

## Performance Notes

- Database indexes on email, tenant_name, status
- Pagination support for large datasets
- Async processing for heavy onboarding tasks
- Optimized queries with criteria pattern

---

**Status**: ✅ Core implementation complete, ready for integration and testing
**Version**: 1.0.0  
**Last Updated**: January 2025