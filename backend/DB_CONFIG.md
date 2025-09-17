# Database Configuration

If you're using XAMPP, WAMP, or another local server:

## Update the database credentials in:
`backend/config/database.php`

Change these lines to match your setup:
```php
private $host = 'localhost';
private $db_name = 'dar_zaid_db';
private $username = 'root';  // Your MySQL username
private $password = '';      // Your MySQL password (if any)
```

## Common setups:

### XAMPP (default):
- Username: `root`
- Password: `` (empty)
- Make sure XAMPP Apache and MySQL are running

### MySQL with password:
- Username: `root` 
- Password: `your_password`

### Docker MySQL:
- Username: `root`
- Password: `your_password`
- Host might be different

## To create the database manually:
1. Open phpMyAdmin or MySQL command line
2. Create database: `CREATE DATABASE dar_zaid_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
3. Run the setup script: `php setup_database.php`

## Or use our SQL file:
Import the `database.sql` file through phpMyAdmin or:
```bash
mysql -u root -p dar_zaid_db < ../database.sql
```
