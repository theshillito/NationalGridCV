# NationalGridCV
Pulling calorific values from National Grid into a database and displaying it based on date range provided

Process:
1) Create database tables - use createDatabase.php for this
2) Populate tables (the National Grid link I was given seems fine for one off data downloads as CSVs or whatever, but there's a SOAP API I can use to loop through the data, so I've used that.) Pull the data down and insert into database using importData.php
3) PHP page to pull data from the database (not directly from National Grid because that's super slow) - use displayData.php for this

Future improvements:
1) Make importData.php interactive to select what data to import into the database, rather than hardcoded year-to-date
2) Database-side filtering for displayData.php (or rather have the data pull in from a separate file called through AJAX probably) as the more the data grows, the slower it will get within DataTables.
