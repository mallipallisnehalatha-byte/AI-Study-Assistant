
import re
import json
from datetime import datetime

# Step 1: Read the timetable text file
file_path = "uploads/sample_timetable.txt"

with open(file_path, "r", encoding="utf-8") as file:
    text = file.read()

# Step 2: Define the weekdays
days = [
    "Monday", "Tuesday", "Wednesday",
    "Thursday", "Friday", "Saturday", "Sunday"
]

# Step 3: Find the period timings
periods = []

for line in text.splitlines():
    if "Days/Periods" in line:
        cells = [cell.strip() for cell in line.split("|")]

        for cell in cells[1:]:
            match = re.search(
                r"(.+?)\s*\((\d{1,2}:\d{2}\s*[AP]M)\s*-\s*(\d{1,2}:\d{2}\s*[AP]M)\)",
                cell,
                re.IGNORECASE
            )

            if match:
                periods.append({
                    "period": match.group(1).strip(),
                    "start": match.group(2).strip(),
                    "end": match.group(3).strip()
                })

        break

# Step 4: Extract subjects and days
timetable = []

for line in text.splitlines():
    cells = [cell.strip() for cell in line.split("|")]

    if len(cells) < 3:
        continue

    day = cells[1].strip()

    if day not in days:
        continue

    # Match each subject with its period
    for index, details in enumerate(cells[2:]):
        details = details.strip()

        if not details:
            continue

        if index >= len(periods):
            print("Warning: No timing found for", details)
            continue

        period = periods[index]

        start_time = datetime.strptime(
            period["start"], "%I:%M%p"
        ).strftime("%H:%M")

        end_time = datetime.strptime(
            period["end"], "%I:%M%p"
        ).strftime("%H:%M")

        # Keep the original text for verification
        subject = details
        room = None

        # Provisional interpretation of entries like DBMS - LH4
        if " - " in details:
            possible_subject, possible_room = details.rsplit(" - ", 1)
            if possible_room.strip().upper().startswith("LH"):
                subject = possible_subject.strip()
                room = possible_room.strip()

        timetable.append({
            "day": day,
            "period": period["period"],
            "start_time": start_time,
            "end_time": end_time,
            "subject": subject,
            "room": room,
            "original_text": details
        })

# Step 5: Display the structured timetable
print("\n===== STRUCTURED TIMETABLE =====\n")

for item in timetable:
    print(item)

# Step 6: Save the timetable as JSON
output_file = "uploads/structured_timetable.json"

with open(output_file, "w", encoding="utf-8") as file:
    json.dump(timetable, file, indent=4)

print("\nTimetable extraction completed!")
print("Total class entries:", len(timetable))
print("Saved to:", output_file)
