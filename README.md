# Tymetry — Todo Management System

A Laravel-based task manager for creating and organizing todos, tracking priorities and due dates, and archiving/restoring tasks.

The current `main` baseline has a successful GitHub Actions run for commit `edc29cbfc4ab2ad0a752969b4d08598ea92fd72b`. CI installs PHP and JavaScript dependencies, builds the frontend assets, and runs the Laravel test suite. This confirms the checks configured in CI; it does not replace manual browser, mail-delivery, or production-environment verification.

## Documentation

Detailed project documentation is maintained in the [docs folder](docs/):

- [Project overview and setup](docs/README.md)
- [Architecture overview](docs/ARCHITECTURE.md)
- [Testing guide and regression checklist](docs/TESTING.md)
- [Code review findings](docs/REVIEW-FINDINGS.md)

## Stack

- PHP / Laravel
- MySQL (configure the database in `.env`)
- Blade, JavaScript, Vite, and Tailwind CSS tooling
- Yajra Laravel DataTables

See the [project setup guide](docs/README.md) for requirements and commands.
