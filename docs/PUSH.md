# Push to GitHub

Branch: `little_lemon`  
Repo: https://github.com/limjunyue66-netizen/Web-based_AR_furniture

## Before you push

Confirm secrets stay local (never commit):

- `config/config.php` — real DB password / production URLs
- `.env` / `.env.*`
- `uploads/thumbs/*` and `models/*` (runtime assets)

Safe templates that **are** committed:

- `config/config.example.php`
- `database.example.php`

## Commands

```powershell
cd C:\xampp\htdocs\Web-based_AR_furniture
git remote add origin https://github.com/limjunyue66-netizen/Web-based_AR_furniture.git
git branch -M little_lemon
git push -u origin little_lemon
```

If GitHub asks for a password, use a **Personal Access Token** (not your GitHub account password):  
https://github.com/settings/tokens

Or sign in with GitHub Desktop / Cursor Source Control, then push `little_lemon`.
