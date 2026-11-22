
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AI Study Assistant</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f1f4fa;
            margin: 0;
            padding: 25px;
        }

        .chat-container {
            max-width: 850px;
            margin: auto;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }

        .header {
            background: #263b63;
            color: white;
            padding: 22px;
            text-align: center;
        }

        .header h1 {
            margin: 0 0 8px;
        }

        .header p {
            margin: 0;
            color: #e0e7f5;
        }

        .status {
            background: #e8f5e9;
            color: #256b35;
            text-align: center;
            padding: 10px;
            font-size: 13px;
        }

        #chatBox {
            height: 440px;
            overflow-y: auto;
            padding: 20px;
            background: #fafbfe;
        }

        .message {
            padding: 13px 16px;
            margin: 12px 0;
            border-radius: 12px;
            max-width: 85%;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            line-height: 1.6;
        }

        .user {
            background: #dce8ff;
            margin-left: auto;
            text-align: right;
        }

        .bot {
            background: #e8f5e9;
            margin-right: auto;
        }

        .input-area {
            display: flex;
            gap: 10px;
            padding: 18px;
            border-top: 1px solid #ddd;
        }

        #userInput {
            flex: 1;
            min-width: 0;
            padding: 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
        }

        #userInput:focus {
            border-color: #263b63;
        }

        button {
            padding: 13px 22px;
            background: #263b63;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .suggestions {
            padding: 0 18px 18px;
        }

        .suggestions p {
            color: #555;
            font-size: 13px;
        }

        .suggestion {
            background: #eef2fa;
            color: #263b63;
            padding: 8px 12px;
            margin: 4px;
            font-size: 13px;
            border: 1px solid #d5dced;
        }

        .clear-btn {
            background: #707b8d;
            padding: 8px 14px;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            body {
                padding: 10px;
            }

            #chatBox {
                height: 55vh;
            }

            .message {
                max-width: 95%;
            }

            .input-area {
                padding: 12px;
            }

            button {
                padding: 12px;
            }
        }
    </style>
</head>

<body>

<div class="chat-container">

    <div class="header">
        <h1>AI Study Assistant</h1>
        <p>Your academic learning companion</p>
    </div>

    <div class="status">
        Offline Educational Assistant Active
    </div>

    <div id="chatBox" aria-live="polite">

        <div class="message bot">
            Hello! Welcome to your Study Assistant.

            I can help you learn topics from:

            • Python
            • C and C++
            • Java
            • DBMS and SQL
            • Artificial Intelligence
            • Machine Learning
            • Data Structures and Algorithms
            • Mathematics
            • Computer Networks
            • Web Development

            Ask me a question to get started!
        </div>

    </div>

    <div class="suggestions">

        <p>Try asking:</p>

        <button class="suggestion" onclick="askQuestion('Explain Python loops')">
            Python Loops
        </button>

        <button class="suggestion" onclick="askQuestion('Explain linked lists')">
            Linked Lists
        </button>

        <button class="suggestion" onclick="askQuestion('What is machine learning?')">
            Machine Learning
        </button>

        <button class="suggestion" onclick="askQuestion('Explain DBMS normalization')">
            DBMS Normalization
        </button>

    </div>

    <div class="input-area">

        <input
            type="text"
            id="userInput"
            placeholder="Ask your academic question..."
            maxlength="2000"
            autocomplete="off"
        >

        <button type="button" id="sendBtn" onclick="sendMessage()">
            Send
        </button>

    </div>

    <div style="padding: 0 18px 18px; text-align: right;">
        <button class="clear-btn" onclick="clearChat()">
            Clear Chat
        </button>
    </div>

</div>

<script>

const input = document.getElementById("userInput");
const chatBox = document.getElementById("chatBox");
const sendBtn = document.getElementById("sendBtn");

// Add messages safely
function addMessage(message, className) {

    const div = document.createElement("div");

    div.className = "message " + className;

    // textContent prevents HTML from being executed
    div.textContent = message;

    chatBox.appendChild(div);

    chatBox.scrollTop = chatBox.scrollHeight;

    return div;
}

// Send a question from suggestion buttons
function askQuestion(question) {

    input.value = question;

    sendMessage();

}

// Send message to PHP backend
async function sendMessage() {

    const message = input.value.trim();

    if (message === "") {
        alert("Please enter your question.");
        return;
    }

    if (message.length > 2000) {
        alert("Your question is too long.");
        return;
    }

    addMessage(message, "user");

    input.value = "";

    sendBtn.disabled = true;
    sendBtn.textContent = "Please wait...";

    const botMessage = addMessage(
        "Preparing your answer...",
        "bot"
    );

    try {

        const formData = new FormData();

        formData.append("message", message);

        const response = await fetch("ai_response.php", {
            method: "POST",
            body: formData
        });

        const rawResponse = await response.text();

        let data;

        try {
            data = JSON.parse(rawResponse);
        } catch (error) {

            throw new Error(
                "Invalid server response. Check your PHP backend."
            );

        }

        if (!response.ok) {

            throw new Error(
                data.reply || "Server error: " + response.status
            );

        }

        botMessage.textContent =
            data.reply || "No answer received.";

    } catch (error) {

        botMessage.textContent =
            "Sorry, something went wrong: " + error.message;

        console.error("Chatbot error:", error);

    } finally {

        sendBtn.disabled = false;
        sendBtn.textContent = "Send";

        input.focus();

        chatBox.scrollTop = chatBox.scrollHeight;

    }

}

// Send question using Enter key
input.addEventListener("keydown", function(event) {

    if (event.key === "Enter") {
        sendMessage();
    }

});

// Clear conversation display
function clearChat() {

    chatBox.innerHTML = "";

    addMessage(
        "Chat cleared! Ask me a new academic question.",
        "bot"
    );

}

</script>

</body>
</html>
