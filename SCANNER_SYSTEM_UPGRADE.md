# Scanner System Upgrade - Multiple Scanners per Event

## Overview

This upgrade transforms the scanner system from a single scanner per event to a flexible multi-scanner system that supports:
- Multiple scanners per event
- Primary and secondary scanner roles
- Active/inactive scanner assignments
- Better scalability for large events

## What Changed

### Before (Old System)
- Single `scanner_person` field in events table
- Limited to one scanner per event
- No role management
- No assignment tracking

### After (New System)
- New `event_scanners` table for many-to-many relationships
- Support for unlimited scanners per event
- Primary/secondary role system
- Assignment tracking with timestamps
- Active/inactive status management

## Database Changes

### New Table: `event_scanners`
```sql
CREATE TABLE event_scanners (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('primary', 'secondary') DEFAULT 'secondary',
    is_active BOOLEAN DEFAULT TRUE,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deactivated_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    UNIQUE KEY unique_event_user (event_id, user_id),
    INDEX idx_event_active (event_id, is_active),
    INDEX idx_user_active (user_id, is_active),
    
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### New Role: `scanner`
- Added to `roles` table
- Users with this role can be assigned as scanners

## New API Endpoints

### Event Scanner Management
- `GET /api/events/{event}/scanners` - Get all scanner assignments for an event
- `POST /api/events/{event}/scanners` - Assign a scanner to an event
- `POST /api/events/{event}/scanners/bulk` - Bulk assign multiple scanners
- `PUT /api/events/{event}/scanners/{scanner}/role` - Update scanner role
- `PATCH /api/events/{event}/scanners/{scanner}/deactivate` - Deactivate scanner
- `PATCH /api/events/{event}/scanners/{scanner}/reactivate` - Reactivate scanner

### Scanner User Management
- `GET /api/scanners/available` - Get all available scanner users
- `GET /api/scanner/my-events` - Get events assigned to authenticated scanner user

## Migration Steps

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed Scanner Role
```bash
php artisan db:seed --class=ScannerRoleSeeder
```

### 3. Migrate Existing Assignments
```bash
php artisan scanners:migrate
```

### 4. Verify Migration
Check that all existing scanner assignments have been migrated:
```bash
php artisan tinker
>>> App\Models\EventScanner::count();
>>> App\Models\Event::whereNotNull('scanner_person')->count();
```

## Usage Examples

### Assign Multiple Scanners to an Event
```php
use App\Models\Event;
use App\Models\EventScanner;

$event = Event::find(1);

// Assign primary scanner
EventScanner::create([
    'event_id' => $event->id,
    'user_id' => 5, // Scanner user ID
    'role' => 'primary',
    'is_active' => true,
]);

// Assign secondary scanners
EventScanner::create([
    'event_id' => $event->id,
    'user_id' => 6,
    'role' => 'secondary',
    'is_active' => true,
]);

EventScanner::create([
    'event_id' => $event->id,
    'user_id' => 7,
    'role' => 'secondary',
    'is_active' => true,
]);
```

### Get All Scanners for an Event
```php
$event = Event::find(1);

// Get all active scanners
$scanners = $event->activeScanners;

// Get primary scanner
$primaryScanner = $event->primaryScannerUser();

// Get secondary scanners
$secondaryScanners = $event->secondaryScannerUsers();

// Check if user is assigned as scanner
$isAssigned = $event->isUserAssignedAsScanner($userId);
```

### Get Events for a Scanner User
```php
$user = User::find(5);

// Get all events where user is assigned as scanner
$scannerEvents = $user->scannerEvents;

// Check if user is assigned to specific event
$isAssigned = $user->isAssignedToEvent($eventId);

// Get scanner role for specific event
$role = $user->getScannerRoleForEvent($eventId);
```

## Flutter App Updates

The Flutter scanner app has been updated to work with the new system:

### API Service Changes
- `getAssignedEvents()` now uses `/api/scanner/my-events`
- Supports multiple events per scanner user
- Better error handling and user feedback

### New Features
- View all assigned events
- Real-time event status updates
- Better offline support
- Improved user experience

## Backward Compatibility

The old `scanner_person` field is maintained for backward compatibility:

- Existing code will continue to work
- The field now returns the primary scanner's name
- Can be safely removed after migration verification

## Admin Panel Updates

The admin panel now supports:

- Assigning multiple scanners per event
- Managing scanner roles (primary/secondary)
- Activating/deactivating scanner assignments
- Bulk scanner management
- Better scanner assignment interface

## Testing

### Test Scanner Assignment
1. Create a user with scanner role
2. Assign user to an event
3. Verify assignment in database
4. Test scanner login and event access

### Test Multiple Scanners
1. Assign multiple scanners to one event
2. Verify only one primary scanner
3. Test scanner role changes
4. Verify deactivation/reactivation

## Rollback Plan

If issues arise, you can rollback:

1. **Database Rollback**
   ```bash
   php artisan migrate:rollback --step=2
   ```

2. **Code Rollback**
   - Revert to previous version
   - Remove new API endpoints
   - Restore old scanner logic

3. **Data Recovery**
   - Old `scanner_person` field remains intact
   - No data loss during migration

## Performance Considerations

- New indexes on `event_scanners` table
- Efficient queries with proper relationships
- Minimal impact on existing performance
- Scalable for large numbers of events and scanners

## Security

- Scanner users can only access assigned events
- Role-based access control maintained
- API endpoints properly protected
- User authentication required for all scanner operations

## Support

For questions or issues with the upgrade:

1. Check migration logs
2. Verify database structure
3. Test API endpoints
4. Review error logs
5. Contact development team

## Future Enhancements

Potential future improvements:

- Scanner performance metrics
- Scanner assignment scheduling
- Advanced role management
- Scanner training and certification
- Integration with external systems
