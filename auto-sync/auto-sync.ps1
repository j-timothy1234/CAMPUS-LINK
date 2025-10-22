# auto-sync.ps1
# Automatically commits and pushes changes to GitHub every 2 minutes

while ($true) {
    Write-Host "🔄 Starting auto-sync..." -ForegroundColor Cyan

    # Navigate to your project folder
    cd "D:\xampp1\htdocs\CAMPUS-LINK"

    #cd D:\xampp1\htdocs\CAMPUS-LINK\auto-sync
    #.\auto-sync.ps1

    # Pull latest changes from remote
    git pull --rebase origin main

    # Stage all modified, new, or deleted files
    git add .

    # Commit changes with a timestamp
    $time = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    git commit -m "Auto sync: project updated at $time"

    # Push changes to GitHub
    git push origin main

    Write-Host "✅ Auto-sync completed at $time" -ForegroundColor Green
    Write-Host "⏳ Waiting 2 minutes before next sync..." -ForegroundColor Yellow

    # Wait for 2 minutes (120 seconds)
    Start-Sleep -Seconds 120
}
