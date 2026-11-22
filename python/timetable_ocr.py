
import os

# Location of timetable text file
file_path = "uploads/sample_timetable.txt"

if os.path.exists(file_path):

    # Read timetable text
    with open(file_path, "r", encoding="utf-8") as file:
        extracted_text = file.read()

    print("\n===== EXTRACTED TIMETABLE =====\n")
    print(extracted_text)

    # Save extracted text
    with open(
        "uploads/extracted_timetable.txt",
        "w",
        encoding="utf-8"
    ) as file:
        file.write(extracted_text)

    print("\nTimetable text read successfully!")

else:
    print("Timetable file not found!")
