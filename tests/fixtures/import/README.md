# Dummy applicant imports

All names, application numbers, emails, and phone numbers are fictional. These workbooks match the portal export's 30 positional columns and section-header format. Each workbook contains exactly one import sheet; do not add an instructions sheet.

Choose **2026-2027** as the academic year on Import Data (the year is supplied by the form).

| File | Expected applicant rows | Expected validation errors |
| --- | --- | --- |
| `dummy-applicants-valid.xlsx` | 18 valid | 0 |
| `dummy-applicants-mixed-errors.xlsx` | The same 18 valid plus 3 invalid | 3 (Excel rows 2, 3, 4) |

The deliberate errors are: no section header, missing APP. NO., and unrecognized SEX. Colored section headers and column headings are not applicant records.

Valid records cover College (one per CITE, CBEAM, CEAS, CIHTM, CON, CCJE), Integrated School (preschool, grade school, JHS, SHS), Graduate Programs (2), Law (2), iPACE (2), and ETEEAP (2). They include varied exam outcomes, payment flags, scholarship/PWD/SSEN flags, and addresses for location-chart testing. Test application numbers start with `DEMO-20261110-`.

Test the clean file first, then the mixed file. The same APP. NO. values are reused deliberately: importing the second file should update the existing 18 applicants, not create another 18. On an empty test dataset, expect 18 applicants after either or both files. Existing database records still contribute to total dashboard counts.

- **Admin:** valid rows should apply immediately; the mixed import should report 3 skipped rows.
- **IATO Staff:** valid rows should be staged in Review Queue and should not change applicant records until an Admin approves. Check My Submissions, then approve or send back the request.
- Download the mixed import's error report and compare the row numbers/reasons above.

College section headers use the application's college codes so Dean filters can be exercised too. A blank applicant name is intentionally not used as an invalid-row example: the current importer treats blank-name rows as headers/template rows and skips them silently.
