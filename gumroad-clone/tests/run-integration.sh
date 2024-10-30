#!/bin/bash

# Phase 1: Database Isolation Tests
echo "Running Database Isolation Tests..."
php artisan test tests/Feature/Store/Isolation/DatabaseIsolationTest.php
php artisan test tests/Feature/Store/Lifecycle/StoreCreationTest.php
php artisan test tests/Feature/Store/Lifecycle/StoreDeletionTest.php

# Only proceed if Phase 1 passes
if [ $? -eq 0 ]; then
    echo "Database Isolation Tests Passed. Proceeding to Revenue Flow Tests..."
    
    # Phase 2: Revenue Flow Tests
    php artisan test tests/Feature/Integration/Billing/PaymentProcessingTest.php
    php artisan test tests/Feature/Integration/Billing/RevenueDistributionTest.php
    php artisan test tests/Feature/Integration/Billing/PayoutProcessingTest.php
    
    # Only proceed if Phase 2 passes
    if [ $? -eq 0 ]; then
        echo "Revenue Flow Tests Passed. Proceeding to Resource Management Tests..."
        
        # Phase 3: Resource Management Tests
        php artisan test tests/Feature/Integration/Billing/UsageTrackingTest.php
        php artisan test tests/Feature/Integration/Billing/TierUpgradeTest.php
        php artisan test tests/Feature/Integration/Billing/UsageEnforcementTest.php
    else
        echo "Revenue Flow Tests Failed. Fix before proceeding."
        exit 1
    fi
else
    echo "Database Isolation Tests Failed. Fix before proceeding."
    exit 1
fi
