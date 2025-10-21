# auto-sync.ps1
# Automatically commits and pushes changes to GitHub

cd "D:\xampp1\htdocs\CAMPUS-LINK"

# Pull latest changes from remote (to stay up-to-date)
git pull --rebase origin main

# Stage all modified, new, or deleted files
git add .

# Commit changes with a timestamp
$time = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
git commit -m "Auto sync: project updated at $time"

# Push changes to GitHub
git push origin main

Write-Host "✅ Auto-sync completed at $time"
