# E2E Tests for OpenSALT VueJS Editor

This directory contains end-to-end (acceptance) tests for the VueJS version of the OpenSALT framework editor. These tests replicate the functionality tested in the PHP Codeception tests found in `core/tests/Acceptance/`.

## Setup

### Prerequisites

- Docker environment running (use `make up` from the project root)
- Node.js dependencies installed (`npm install`)

### Installation

```bash
npm install --save-dev @playwright/test
npx playwright install chromium
```

## Running Tests

### Run all E2E tests

```bash
npm run test:e2e
```

### Run tests with UI

```bash
npm run test:e2e:ui
```

### Run tests in debug mode

```bash
npm run test:e2e:debug
```

### Run tests in headed mode (visible browser)

```bash
npm run test:e2e:headed
```

## Test Files

| Test File | Description |
|-----------|-------------|
| `cfdoc.spec.ts` | Tests for CF Document create/import button visibility based on user role |
| `doctree.spec.ts` | Tests for document tree ordering |
| `dragger.spec.ts` | Tests for side panel drag and toggle functionality |
| `editbarbutton.spec.ts` | Tests for edit button and alphabetical sort feature |
| `fullscreenmodal.spec.ts` | Tests for full screen edit modal |
| `importchildren.spec.ts` | Tests for CSV import functionality |
| `commentdoc.spec.ts` | Tests for document comments functionality |
| `loginview.spec.ts` | Tests for login view |

## Test Structure

### Fixtures

The `fixtures.ts` file provides:
- `loginPage`: Helper for user authentication
- `lastFrameworkId`: Fixture to get the last created framework ID
- `expect`: Extended Playwright expect

### Login Helper

The `LoginPage` class provides methods for:
- `logout()`: Logout current user
- `loginAsRole(role)`: Login with a specific role (Editor, Admin, User, Super User)
- `loginWithPassword(username, password)`: Login with specific credentials

### Test Data

Tests use temporary files for data uploads. These files are created in the system's temp directory and cleaned up after tests complete.

## Notes

- Tests use `http://web.salt-default` as the base URL
- Test users are expected to exist in the database (created via fixtures or seed data)
- Some tests may require specific test data to be available in the database
- The VueJS editor is tested alongside the jQuery version to ensure feature parity

## Troubleshooting

### Browser not launching

Make sure Playwright browsers are installed:

```bash
npx playwright install
```

### Tests failing due to authentication

Ensure test users exist in the database. Check the PHP Codeception tests for user creation logic.

### Timeout errors

Increase timeouts in `playwright.config.js` or use `--timeout` flag when running tests.

### Network issues

Ensure Docker services are running with `make up` and the application is accessible at `http://web.salt-default`.
