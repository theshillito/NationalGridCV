# NationalGridCV
Pulling calorific values from National Grid into a database and displaying it based on date range provided

Planned process:
1) Create database tables (could make this a script to create tables if not exists)
2) Populate tables (the National Grid link I was given seems fine for one off data downloads as CSVs or whatever, but there's a SOAP API I can use to loop through the API and populate based on a start and end date)
3) PHP page to pull data from the database (not directly from National Grid because that's super slow) based off a date range or everything I guess