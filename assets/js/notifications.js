
if ("Notification" in window) {

    Notification.requestPermission().then(function(permission) {

        if (permission === "granted") {

            console.log("Notification permission granted!");

            checkReminders();

            setInterval(checkReminders, 30000);
        }
    });
}

function checkReminders() {

    fetch("check_reminders.php")
        .then(response => response.json())
        .then(data => {

            if (data.reminders) {

                data.reminders.forEach(function(reminder) {

                    new Notification(
                        "Student Academic Assistant",
                        {
                            body: reminder.reminder_text
                        }
                    );

                });

            }

        })
        .catch(error => {
            console.error("Reminder error:", error);
        });
}
