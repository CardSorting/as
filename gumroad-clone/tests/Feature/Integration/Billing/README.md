# Billing Integration Tests

This directory contains tests that verify the billing relationships between admin and child containers.

## Test Hierarchy (in order of importance)

1. Database Isolation (✓ PASSED)
   - Store database creation
   - Data separation
   - Operation isolation

2. Revenue Flow (NEXT)
   - Payment processing
   - Revenue sharing
   - Balance tracking

3. Resource Management
   - Storage allocation
   - Usage limits
   - Tier enforcement

## Running Tests

Use the provided script to run tests in sequence:

```bash
./tests/run-integration.sh
```

This will ensure tests are run in the correct order and dependencies are properly verified.
