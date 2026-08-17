' WhatsDesk cron worker - hidden launcher (no cmd window)
' Runs the CI cron every time Windows Task Scheduler calls it.
' Window style 0 = completely hidden.
Set sh = CreateObject("WScript.Shell")
sh.Run "C:\xampp_new\php\php.exe C:\xampp_new\htdocs\whatsapp_chatbot\index.php cron run", 0, False
