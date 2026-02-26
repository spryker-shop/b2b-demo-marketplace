# Deployment View

Technical infrastructure used to execute and run the system.

**Note:** This section is especially important for non-standard, on-premises, or hybrid environments where deployment specifics differ from typical cloud setups.

## Infrastructure Overview

### Production Environment

| Component | Technology | Purpose |
|-----------|-----------|---------|
| **Load Balancer** | Cloud LB | Traffic distribution |
| **Application Pods** | Kubernetes | Application runtime |
| **Database** | PostgreSQL (managed) | Primary data storage |
| **Cache** | Redis (managed) | Session and cache storage |
| **Search** | Elasticsearch (managed) | Product search |
| **Message Queue** | RabbitMQ | Async processing |
| **Object Storage** | S3 | Media and files |

### Deployment Pattern

- **Container Orchestration**: Kubernetes
- **CI/CD**: Automated pipeline with blue-green deployment
- **Monitoring**: Centralized logging and metrics
- **Backup**: Automated daily backups with 30-day retention

---

*Corresponds to [arc42 Section 7](https://docs.arc42.org/section-7/)*
