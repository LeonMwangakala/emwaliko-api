# Event Status Update Command

The `events:update-status` command allows you to update event statuses with various options and validations.

## Usage

```bash
php artisan events:update-status [options]
```

## Options

- `--event-id=ID` - Update specific event by ID
- `--event-code=CODE` - Update specific event by event code
- `--status=STATUS` - New status to set (initiated, inprogress, notified, scanned, completed, cancelled)
- `--auto` - Automatically determine and update status based on event data
- `--dry-run` - Show what would be updated without making changes
- `--force` - Force update even if status validation fails

## Valid Statuses

- `initiated` - Event created but no guests added
- `inprogress` - Event has guests but no notifications sent
- `notified` - Notifications have been sent to guests
- `scanned` - Guests have been scanned at the event
- `completed` - Event is finished and paid
- `cancelled` - Event has been cancelled

## Status Transition Rules

The command enforces valid status transitions unless `--force` is used:

- `initiated` → `inprogress`, `cancelled`
- `inprogress` → `notified`, `cancelled`
- `notified` → `scanned`, `cancelled`
- `scanned` → `completed`, `cancelled`
- `completed` → (no further transitions)
- `cancelled` → (no further transitions)

## Examples

### 1. Auto-update all events based on their data
```bash
php artisan events:update-status --auto
```

### 2. Update specific event by ID
```bash
php artisan events:update-status --event-id=5 --status=inprogress
```

### 3. Update specific event by event code
```bash
php artisan events:update-status --event-code=EVENT123 --status=scanned
```

### 4. Dry run to see what would be updated
```bash
php artisan events:update-status --auto --dry-run
```

### 5. Force update (bypass status transition validation)
```bash
php artisan events:update-status --event-id=2 --status=scanned --force
```

### 6. Update multiple events with auto-detection
```bash
php artisan events:update-status --auto --dry-run
```

## Auto-Detection Logic

When using `--auto`, the command determines status based on:

1. **cancelled** - If current status is cancelled
2. **completed** - If sales are paid and event date has passed
3. **scanned** - If guests have been scanned
4. **notified** - If notifications have been sent
5. **inprogress** - If event has guests
6. **initiated** - Default (no guests)

## Safety Features

- **Dry Run**: Use `--dry-run` to see what would be updated without making changes
- **Validation**: Status transitions are validated unless `--force` is used
- **Error Handling**: Errors are caught and reported without stopping the entire process
- **Summary**: Provides a summary of processed events at the end

## Best Practices

1. Always use `--dry-run` first to see what changes would be made
2. Use `--auto` for bulk updates when you want the system to determine appropriate statuses
3. Use `--force` only when you're certain about bypassing validation
4. Test on a small subset of events before running on all events 