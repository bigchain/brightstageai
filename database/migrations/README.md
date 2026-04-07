# Database Migrations

Run these SQL files in order in phpMyAdmin.

## How to run:
1. Open phpMyAdmin
2. Select database: `convertpods_iu`
3. Go to SQL tab
4. Copy-paste the migration file content
5. Click "Go"

## Migration files:
| File | Description | Status |
|------|-------------|--------|
| `001_initial_schema.sql` | All 9 tables + seed data | Run first |

## Future migrations:
Name files as `002_description.sql`, `003_description.sql`, etc.
Always run in order. Never modify a migration that's already been applied — create a new one instead.
