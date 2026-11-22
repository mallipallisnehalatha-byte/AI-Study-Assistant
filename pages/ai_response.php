
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../config/database.php";

function sendResponse($reply, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode(
        ["reply" => $reply],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

function hasAny($message, $keywords) {
    foreach ($keywords as $keyword) {
        if (strpos($message, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

function localAnswer($question) {

    $q = strtolower(trim($question));

    // PYTHON
    if (hasAny($q, ["python loop", "loops in python", "for loop", "while loop"])) {
        return "PYTHON LOOPS\n\nLoops are used to execute a block of code repeatedly.\n\n1. For loop: Repeats code for each item in a sequence.\n\nExample:\nfor i in range(5):\n    print(i)\n\nOutput: 0 1 2 3 4\n\n2. While loop: Repeats code while a condition is true.\n\nExample:\ni = 1\nwhile i <= 5:\n    print(i)\n    i += 1\n\nBoth loops help reduce repeated code.";
    }

    if (hasAny($q, ["python variable", "variables in python"])) {
        return "PYTHON VARIABLES\n\nA variable stores a value in memory.\n\nExample:\nname = 'Alex'\nage = 20\nmarks = 90\n\nPython automatically identifies the data type of a variable.";
    }

    if (hasAny($q, ["python function", "functions in python"])) {
        return "PYTHON FUNCTIONS\n\nA function is a reusable block of code.\n\nExample:\ndef greet():\n    print('Hello')\n\ngreet()\n\nThe def keyword is used to define a function.";
    }

    if (hasAny($q, ["python list", "lists in python"])) {
        return "PYTHON LISTS\n\nA list stores multiple values in one variable.\n\nExample:\nfruits = ['apple', 'mango', 'orange']\nprint(fruits[0])\n\nOutput: apple\n\nLists are ordered and can be modified.";
    }

    // C PROGRAMMING
    if (hasAny($q, ["c loop", "loops in c", "c for loop", "c while loop"])) {
        return "LOOPS IN C\n\nLoops execute a block of statements repeatedly.\n\n1. for loop\n2. while loop\n3. do-while loop\n\nExample:\n#include <stdio.h>\nint main() {\n    for(int i = 1; i <= 5; i++) {\n        printf('%d', i);\n    }\n    return 0;\n}\n\nA for loop contains initialization, condition and update.";
    }

    if (hasAny($q, ["pointer", "pointers in c"])) {
        return "POINTERS IN C\n\nA pointer is a variable that stores the memory address of another variable.\n\nExample:\nint a = 10;\nint *p = &a;\n\nHere, &a gives the address of a and p stores that address. *p accesses the value.";
    }

    if (hasAny($q, ["array", "arrays in c"])) {
        return "ARRAYS\n\nAn array stores multiple values of the same data type in continuous memory locations.\n\nExample:\nint marks[3] = {80, 90, 75};\n\nArray indexing starts from 0. marks[0] gives 80.";
    }

    // C++
    if (hasAny($q, ["c++", "cpp", "c plus plus"])) {
        return "C++ PROGRAMMING\n\nC++ is a general-purpose programming language that supports procedural and object-oriented programming.\n\nImportant concepts:\n1. Classes and objects\n2. Inheritance\n3. Polymorphism\n4. Encapsulation\n5. Abstraction\n6. Functions and loops\n\nAsk about any individual C++ concept for a more focused explanation.";
    }

    // JAVA
    if (hasAny($q, ["java loop", "loops in java"])) {
        return "LOOPS IN JAVA\n\nJava supports for, while and do-while loops.\n\nExample:\nfor(int i = 1; i <= 5; i++) {\n    System.out.println(i);\n}\n\nThis prints numbers from 1 to 5.";
    }

    if (hasAny($q, ["java", "oops", "object oriented"])) {
        return "JAVA AND OOP\n\nJava is an object-oriented programming language.\n\nFour important OOP principles:\n1. Encapsulation: Combining data and methods.\n2. Inheritance: Reusing properties of another class.\n3. Polymorphism: One interface with different behaviours.\n4. Abstraction: Hiding unnecessary implementation details.";
    }

    // DBMS
    if (hasAny($q, ["normalization", "1nf", "2nf", "3nf"])) {
        return "DBMS NORMALIZATION\n\nNormalization organizes database tables to reduce data duplication and improve consistency.\n\n1NF: Each column contains atomic values.\n2NF: 1NF plus no partial dependency on a candidate key.\n3NF: 2NF plus no transitive dependency of non-key attributes on a candidate key.\n\nNormalization helps maintain reliable data.";
    }

    if (hasAny($q, ["primary key", "foreign key", "candidate key", "super key"])) {
        return "DBMS KEYS\n\nPrimary Key: Uniquely identifies each row in a table.\n\nForeign Key: Refers to a key in another table and helps maintain relationships.\n\nCandidate Key: A minimal set of attributes that uniquely identifies a row.\n\nSuper Key: Any set of attributes that uniquely identifies a row.";
    }

    if (hasAny($q, ["sql", "select query", "insert query", "update query", "delete query"])) {
        return "SQL\n\nSQL stands for Structured Query Language. It is used to communicate with relational databases.\n\nExamples:\n\nSELECT * FROM students;\n\nINSERT INTO students VALUES (1, 'Alex');\n\nUPDATE students SET name = 'Sam' WHERE id = 1;\n\nDELETE FROM students WHERE id = 1;\n\nSQL supports creating, reading, updating and deleting data.";
    }

    if (hasAny($q, ["dbms", "database management"])) {
        return "DBMS\n\nDBMS stands for Database Management System.\n\nIt is software used to create, store, organize, retrieve and manage data.\n\nExamples: MySQL, Oracle and PostgreSQL.\n\nAdvantages:\n1. Reduces data duplication.\n2. Improves security.\n3. Maintains data consistency.\n4. Supports data sharing.\n5. Provides backup and recovery.";
    }

    // ARTIFICIAL INTELLIGENCE
    if (hasAny($q, ["machine learning", "ml"])) {
        return "MACHINE LEARNING\n\nMachine Learning is a branch of Artificial Intelligence that allows computers to learn patterns from data and make predictions.\n\nTypes:\n1. Supervised learning\n2. Unsupervised learning\n3. Reinforcement learning\n\nExample: An email spam filter learns to identify unwanted emails.";
    }

    if (hasAny($q, ["artificial intelligence", "what is ai", "about ai"])) {
        return "ARTIFICIAL INTELLIGENCE\n\nArtificial Intelligence (AI) is a field of computer science that develops systems capable of performing tasks that usually require human intelligence.\n\nApplications:\n1. Voice assistants\n2. Image recognition\n3. Recommendation systems\n4. Chatbots\n5. Medical data analysis\n\nMachine Learning is one branch of AI.";
    }

    // DATA STRUCTURES
    if (hasAny($q, ["linked list", "linked lists"])) {
        return "LINKED LIST\n\nA linked list is a linear data structure made up of nodes.\n\nEach node contains:\n1. Data\n2. A link to the next node\n\nTypes:\n1. Singly linked list\n2. Doubly linked list\n3. Circular linked list\n\nCommon operations include insertion, deletion and traversal.";
    }

    if (hasAny($q, ["stack"])) {
        return "STACK\n\nA stack is a linear data structure that follows LIFO: Last In, First Out.\n\nOperations:\n1. Push: Insert an element.\n2. Pop: Remove the top element.\n3. Peek: View the top element.\n\nExample: A stack of plates.";
    }

    if (hasAny($q, ["queue"])) {
        return "QUEUE\n\nA queue is a linear data structure that follows FIFO: First In, First Out.\n\nOperations:\n1. Enqueue: Insert an element.\n2. Dequeue: Remove an element.\n3. Front: View the first element.\n\nExample: People waiting in a line.";
    }

    if (hasAny($q, ["sorting", "bubble sort", "quick sort", "merge sort"])) {
        return "SORTING ALGORITHMS\n\nSorting arranges data in a particular order, such as ascending or descending.\n\nExamples:\n1. Bubble Sort\n2. Selection Sort\n3. Insertion Sort\n4. Merge Sort\n5. Quick Sort\n\nDifferent algorithms have different time and space complexities.";
    }

    // MATHEMATICS
    if (hasAny($q, ["permutation", "permutations"])) {
        return "PERMUTATION\n\nPermutation means arranging objects in a specific order.\n\nFormula:\n nPr = n! / (n-r)!\n\nExample:\nThe number of ways to arrange 2 objects selected from 3 is:\n3P2 = 3! / 1! = 6.";
    }

    if (hasAny($q, ["combination", "combinations"])) {
        return "COMBINATION\n\nCombination means selecting objects when order does not matter.\n\nFormula:\n nCr = n! / (r! (n-r)!)\n\nExample:\n3C2 = 3! / (2! x 1!) = 3.";
    }

    if (hasAny($q, ["probability"])) {
        return "PROBABILITY\n\nProbability measures how likely an event is to happen.\n\nFormula:\nP(E) = Favourable outcomes / Total equally likely outcomes\n\nExample:\nThe probability of getting heads when tossing a fair coin is 1/2.";
    }

    // COMPUTER NETWORKS
    if (hasAny($q, ["osi model", "computer network", "networking"])) {
        return "COMPUTER NETWORKS\n\nA computer network connects devices so they can exchange data and share resources.\n\nThe OSI model has seven layers:\n1. Physical\n2. Data Link\n3. Network\n4. Transport\n5. Session\n6. Presentation\n7. Application";
    }

    // WEB DEVELOPMENT
    if (hasAny($q, ["html", "css", "javascript", "web development"])) {
        return "WEB DEVELOPMENT\n\nHTML: Defines the structure of a webpage.\n\nCSS: Controls its appearance, colours and layout.\n\nJavaScript: Adds interactivity and dynamic behaviour.\n\nPHP can handle server-side operations, while MySQL stores application data.";
    }

// HTML
if (preg_match('/\bhtml\b/', $q)) {
    return "HTML\n\nHTML stands for HyperText Markup Language.\n\nIt is used to create the structure of a webpage.\n\nExample:\n<h1>Hello World</h1>\n<p>Welcome to my website.</p>\n\nHTML defines headings, paragraphs, images, links and forms.";
}

// CSS
if (preg_match('/\bcss\b/', $q)) {
    return "CSS\n\nCSS stands for Cascading Style Sheets.\n\nIt is used to style HTML webpages.\n\nCSS controls:\n1. Colours\n2. Fonts\n3. Backgrounds\n4. Spacing\n5. Page layout\n\nExample:\nbody {\n    background-color: lightblue;\n}\n\nCSS makes webpages attractive and well-organized.";
}

// JAVASCRIPT
if (preg_match('/\bjavascript\b/', $q)) {
    return "JAVASCRIPT\n\nJavaScript is a programming language used to make webpages interactive.\n\nIt can:\n1. Handle button clicks.\n2. Validate forms.\n3. Update webpage content.\n4. Create interactive features.\n\nExample:\nalert('Hello World');\n\nJavaScript adds dynamic behaviour to webpages.";
}

// JAVA
if (preg_match('/\bjava\b/', $q) || hasAny($q, ["oops", "object oriented"])) {
    return "JAVA AND OOP\n\nJava is an object-oriented programming language.\n\nFour important OOP principles:\n1. Encapsulation: Combining data and methods.\n2. Inheritance: Reusing properties of another class.\n3. Polymorphism: One interface with different behaviours.\n4. Abstraction: Hiding unnecessary implementation details.";
}

// MACHINE LEARNING
if (hasAny($q, ["machine learning"]) || preg_match('/\bml\b/', $q)) {
    return "MACHINE LEARNING\n\nMachine Learning is a branch of Artificial Intelligence that allows computers to learn patterns from data and make predictions.\n\nTypes:\n1. Supervised learning\n2. Unsupervised learning\n3. Reinforcement learning\n\nExample: An email spam filter learns to identify unwanted emails.";
}

// GENERAL WEB DEVELOPMENT
if (hasAny($q, ["web development"])) {
    return "WEB DEVELOPMENT\n\nHTML: Defines webpage structure.\nCSS: Controls webpage appearance and layout.\nJavaScript: Adds interactivity and dynamic behaviour.\nPHP: Handles server-side operations.\nMySQL: Stores application data.";
}

    // DATA STRUCTURES AND ALGORITHMS (DSA)

    if (hasAny($q, ["what is data structure", "what are data structures", "data structure and algorithm", "data structures and algorithms", "what is dsa"])) {
        return "DATA STRUCTURES AND ALGORITHMS (DSA)\n\nData Structures and Algorithms are important concepts in computer science.\n\n1. Data Structure:\nA data structure is a way of organizing and storing data so that it can be accessed and modified efficiently.\n\nExamples:\n- Array\n- Linked List\n- Stack\n- Queue\n- Tree\n- Graph\n\n2. Algorithm:\nAn algorithm is a step-by-step procedure used to solve a particular problem.\n\nExamples:\n- Searching algorithms\n- Sorting algorithms\n- Binary Search\n- Linear Search\n\nApplications:\n1. Organizing data\n2. Searching information\n3. Sorting records\n4. Solving computational problems\n\nData structures organize data, while algorithms perform operations to solve problems.";
    }

    if (hasAny($q, ["what is algorithm", "define algorithm", "algorithms in computer science"])) {
        return "ALGORITHM\n\nAn algorithm is a finite sequence of clear, step-by-step instructions used to solve a problem.\n\nCharacteristics:\n1. Input\n2. Output\n3. Definiteness\n4. Finiteness\n5. Effectiveness\n\nExample: An algorithm to add two numbers:\n1. Start\n2. Read two numbers A and B\n3. Calculate Sum = A + B\n4. Display Sum\n5. Stop";
    }


    // GENERAL ACADEMIC FALLBACK
    return "I am your offline Study Assistant. I can currently explain topics from Python, C, C++, Java, DBMS, SQL, Artificial Intelligence, Machine Learning, Data Structures, Algorithms, Mathematics, Computer Networks and Web Development.\n\nPlease mention the specific topic in your question. For example:\n\n1. Explain Python for loops.\n2. What is a linked list?\n3. Explain primary and foreign keys.\n4. What is machine learning?\n5. Explain the OSI model.";
}

// Validate request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse("Invalid request method.", 405);
}

$message = trim($_POST["message"] ?? "");

if ($message === "" || strlen($message) > 2000) {
    sendResponse("Please enter a valid question.", 400);
}

// Generate local answer
$reply = localAnswer($message);

// Save chat history if database is available
try {
    if (isset($conn)) {
        $log = $conn->prepare(
            "INSERT INTO chat_history
            (student_id, user_message, ai_response)
            VALUES (?, ?, ?)"
        );

        $log->execute([1, $message, $reply]);
    }
} catch (Throwable $e) {
    error_log("Chat history error: " . $e->getMessage());
}

// Return answer
sendResponse($reply);

?>
