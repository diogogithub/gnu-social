Submission Checklist
================================================================================
This document serves as a handy checklist for submitted merges and patches to
the postActiv project.  Following it isn't a gaurantee a patch will be accepted,
but it will help you avoid common problems.

1. Ensure all code control paths in all functions return a value.

2. Ensure all exceptions are trapped in an exception class, or minimally,
   written to the log with common_log

3. Ensure the coding format standards are adhered to (see coding_standards.md)

4. Ensure that any new class that deals in public data has a corresponding new
   API endpoint.

5. Ensure that all new API endpoints sanitize inputs and outputs properly.

6. Ensure that your version of the code works with PHP 7 on a standard
   LAMP and LEMP stack (Linux+Apache+MariaDB+PHP and Linux+nginx+MariaDB+PHP)

7. If implementing new database functions, ensure they work with MariaDB
   and postgreSQL.

8. Ensure all data that federates does so properly and has mechanisms to
   catch and accomodate for federation transmission failure.

9. Ensure that nothing is left in an error state when it is avoidable.

10. Ensure that all code submitted is properly documented.

11. Ensure that there are no PHP Strict Standards or Parse errors in the code.
