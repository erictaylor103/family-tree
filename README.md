# Family Tree Web Application

A web-based family tree application that allows users to manage and visualize family relationships.

## Features

- Add and edit family members
- Manage marriages and relationships
- Upload and display profile photos
- Visualize family tree with parent-child relationships
- Track birth and death dates
- Record birth locations
- Edit family member information

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP (or similar local development environment)
- Web browser with JavaScript enabled

## Installation

1. Clone the repository:
```bash
git clone [your-repository-url]
```

2. Set up your database:
   - Create a new MySQL database
   - Import the database schema from `database/schema.sql`

3. Configure the database connection:
   - Copy `config/database.example.php` to `config/database.php`
   - Update the database credentials in `config/database.php`

4. Set up your web server:
   - Point your web server to the project directory
   - Ensure the `uploads` directory is writable

## Usage

1. Access the application through your web browser
2. Start by adding family members
3. Create relationships between family members
4. View the family tree visualization

## Directory Structure

```
familytree/
├── config/         # Configuration files
├── database/       # Database schema and migrations
├── includes/       # Common PHP includes
├── pages/         # Main application pages
├── uploads/       # Uploaded photos
└── README.md      # This file
```

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details. 