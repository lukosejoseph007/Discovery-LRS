# What-To-Update

## Instructions for Updating Files When Changes Occur

### Database and Table Changes

Whenever there is a change to the database and table in phpMyAdmin, ensure that you make the necessary modifications in the `statements-database-table.sql` file.

#### Export the Table Structure

1. **Open phpMyAdmin**: Log in to your phpMyAdmin interface.
2. **Select the Database**: Choose the database that contains the table you want to replicate.
3. **Select the Table**: Click on the table you want to replicate.
4. **Export the Table**:
   - Click on the "Export" tab at the top of the page.
   - In the "Export Method" section, choose "Custom - display all possible options".
   - In the "Format" section, select "SQL".
   - In the "Tables" section, select the table you want to export. Ensure that only the "Structure" option is checked (uncheck "Data" if you don't want to include the data).
   - In the "Object creation options" section, ensure that "Add CREATE TABLE statement" is checked.
   - Optionally, you can choose other settings like "Enclose table and column names with backquotes" to protect special characters or keywords.
   - In the "Output" section, you can choose to "View output as text" or "Save output to a file". If you choose to save to a file, specify a file name template if needed.
   - Click on the "Go" button to export the SQL commands.

### xAPI Statements Changes

Whenever there is a change in the `xapi-statements.js` file, ensure that you add it in the `sample-xapi-statements.js` file.

### sendStatement Command Changes

Whenever there is a change in the structure of the `sendStatement` command that is added in the "Execute Javascript" part of Articulate Storyline, update it in the `sample-user.js` file and the `sendStatement.md` file.
