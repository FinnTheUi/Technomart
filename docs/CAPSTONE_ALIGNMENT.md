# Capstone alignment corrections

Use this as the source of truth when revising the manuscript, diagrams, and screenshots.

## Roles

Rentelligence has exactly three authenticated roles: **Admin, Manager, and Tenant**. References to Cashier or Warehouse Staff are incorrect. Their operational duties are intentionally contained within the Admin and Manager roles.

## Public access

Guests may view stall availability, submit a rental application, join a waitlist, and use signed applicant payment/status pages. Supply ordering, internal dashboards, contracts, messages, bills, and maintenance features require an authenticated and verified account.

## Predictive analytics

The model is decision support, not an automatic approval, rejection, or termination mechanism. Managers see a probability, risk band, plain-language factors, source (model or historical fallback), training population, and evaluation metrics. Human review remains required.

## Local and production environments

The current development environment uses SQLite. Production should use MySQL, HTTPS, a persistent queue worker, a scheduler process, backups, and valid SMTP credentials.

