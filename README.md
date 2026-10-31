# Personal AI Quiz Creator (local XAMPP)

A single-user PHP application that saves handouts and quiz history in a local MariaDB database. AI requests run on the PHP server and use only the uploaded handout text in the prompt. AI output is untrusted and should be reviewed against the quoted source.

## 1. Required software

- Windows 10/11 and XAMPP with PHP 8.1+ and MariaDB/MySQL.
- Composer 2 for PDF parsing (`smalot/pdfparser`). Install Composer from [getcomposer.org](https://getcomposer.org/) and reopen PowerShell after installation.
- An OpenAI API key or Google Gemini API key for generation. Provider prices, rate limits, and account eligibility vary; do not assume use is free.
- PHP extensions `pdo_mysql`, `curl`, `fileinfo`, `mbstring`, `dom`, `xml`, and `zip` enabled in XAMPP's `php.ini`.

## 2. Project folder and services

The project root is C:\xampp\htdocs\quiz-creator, matching the local URL below. Start **Apache** and **MySQL** from the XAMPP Control Panel.

Open `http://localhost/quiz-creator/`. If you keep the nested checkout path, use `http://localhost/quiz-creator/quiz-creator/` and set `APP_URL` to that URL.

## 3. Create the database

1. Open `http://localhost/phpmyadmin/`.
2. Choose **Import**, select `C:\xampp\htdocs\quiz-creator\database\schema.sql` (adjust if nested), and choose **Go**. The script safely uses `CREATE DATABASE IF NOT EXISTS`; it does not drop existing data.
3. The default database/user are `quiz_creator` and `root`, with a blank password in a fresh XAMPP install. Set different values in the server environment if you changed MariaDB credentials.

## 4. Install Composer dependency

Open PowerShell in the project directory and run:

```powershell
cd C:\xampp\htdocs\quiz-creator
composer install
```

If Composer is missing, install it first and ensure `composer` is on PATH. PDF parsing is unavailable until this finishes. DOCX/PPTX require PHP's `zip` extension. Images are accepted and safely stored, but OCR is **not implemented/configured**: the app clearly records an extraction failure and will not pretend image text was read. Scanned PDF/image OCR also remains unavailable.

## 5. Provider configuration and Windows Apache environment

Exact variables are listed in `.env.example`. Supported names:

- Common: `APP_ENV`, `APP_DEBUG`, `APP_URL`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `AI_PROVIDER`, `AI_TIMEOUT`, `MAX_UPLOAD_MB`.
- OpenAI: `OPENAI_API_KEY`, `OPENAI_MODEL` (default `gpt-4.1-mini`). Uses OpenAI's Responses endpoint and JSON Schema structured output.
- Gemini: `GEMINI_API_KEY`, `GEMINI_MODEL` (default `gemini-2.5-flash`). Uses Gemini `generateContent` and JSON schema output.

Never put real keys in Git, HTML, JavaScript, database tables, or a publicly served file. The app loads the project-root .env file on the server before reading configuration. The real .env is ignored by Git; .env.example contains placeholders only. The local .env file takes precedence over process-level variables. Never put a real key in a commit.

Local XAMPP setup: copy .env.example to C:\xampp\htdocs\quiz-creator\.env if you need a fresh template, then edit .env in VS Code. Set AI_PROVIDER=gemini, GEMINI_API_KEY to your private key, and GEMINI_MODEL=gemini-2.5-flash. Leave OPENAI_API_KEY blank if unused. Save the file and refresh the app; no Apache restart is normally needed for .env edits. The loader preserves quoted key characters and does not expand dollar signs or backslashes. Never paste the real key in chat, screenshots, Git, or browser code.

The root .htaccess denies HTTP access to .env, .env.* and .git; storage/.htaccess denies requests to private uploads and text. These rules require Apache override access (AllowOverride AuthConfig or AllowOverride All) and mod_authz_core on Apache 2.4. Test directly by requesting http://localhost/quiz-creator/.env; Apache should return 403 or 404, never file content. If it returns another status, stop Apache and check C:\xampp\apache\conf\httpd.conf for AllowOverride None, enable the appropriate override for the project directory, and restart Apache. Settings reports only whether the selected key is present.

## 6. Use the app

1. Open `http://localhost/quiz-creator/`.
2. Choose **Settings → Test provider connection** after configuring a key.
3. Choose **Upload a handout**, select a PDF, DOCX, PPTX, TXT, Markdown, or common image, and upload.
4. Review the extraction preview and status. Fix extraction issues before proceeding.
5. Choose **Configure study set**, select topic/count/difficulty/types/mode, read the generation summary, and generate.
6. Submit practice answers to see results, source excerpts, score, and review status. Use history to reopen results or repeat automatically missed questions.

Custom instructions cannot override source-only rules. The app asks the provider to quote exact excerpts and validates basic item shape, answer labels, duplicate text, and presence of source evidence. Semantic support and quotations still require human review; AI validation is not infallible.

## 7. Tests and checks

Run `php tests/run.php` and check PHP syntax with:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

Live database initialization, upload through Apache, provider connectivity, and real document parsing require XAMPP services, Composer dependencies, and a configured provider key. The test file covers pure validation/grading/CSRF behavior only; it does not claim external integration tests.

## 8. Troubleshooting

- **Database unavailable:** start MySQL; import `database/schema.sql`; verify DB values in the Apache config and restart Apache.
- **Provider key missing:** check that .env is readable by Apache and AI_PROVIDER=gemini is selected in Settings.
- **401/403:** recheck key, selected provider, account access, and model availability.
- **429/timeout:** wait, check the provider dashboard and account limits, then retry. No alternate provider is selected automatically.
- **PDF needs Composer:** run `composer install` in this project folder and confirm `vendor/autoload.php` exists.
- **DOCX/PPTX failure:** enable `extension=zip` in the active XAMPP `php.ini` and restart Apache.
- **Scanned pages/images:** text extraction cannot read pixels. OCR is not configured, so use a searchable text PDF or a verified text copy; do not treat the upload as extracted.
- **403 session check:** reload and submit again; CSRF protection rejects stale forms.

## 9. Privacy, backups, and limitations

Uploads and extracted text live in `storage/` outside public URLs and are ignored by Git. Requests send extracted handout content to the provider you configured; check that provider's privacy and retention terms before upload. API usage can incur charges. Back up the `quiz_creator` database through phpMyAdmin Export and separately back up `storage/uploads`; both may contain private study material. Delete a handout in the UI to remove linked quizzes, attempts, and stored file.

This is for local personal use, not hardened for public internet deployment. It has no accounts, OCR engine, section/chapter parser, true page-aware PDF extraction validation, semantic quote verification, answer-explanation free-form concept picker, timer, or generated cram summaries independent from quiz items. Explanations and cram mode display supported generated quiz material. Repeat incorrect reuses the original missed questions (no variants). Short answer grading only auto-accepts exact normalized key matches; other responses await manual review. A simple local request throttle and automated semantic validation are not implemented.

## 10. Provider references

- [OpenAI Responses API](https://platform.openai.com/docs/api-reference/responses) and [Structured Outputs](https://platform.openai.com/docs/guides/structured-outputs)
- [Gemini generateContent API](https://ai.google.dev/api/generate-content) and [Structured outputs](https://ai.google.dev/gemini-api/docs/structured-output)

Configured model names are examples/defaults and remain editable. Confirm currently available models and pricing in the provider's account before use.
