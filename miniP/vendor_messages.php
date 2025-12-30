<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Messaging - Farmer's Market</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: url('https://img.freepik.com/free-photo/farmers-market-with-fresh-produce_1150-30602.jpg') center/cover no-repeat;
            min-height: 100vh;
            color: #333;
        }

        header {
            width: 100%;
            background: rgba(43, 122, 11, 0.9);
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        header .logo {
            font-size: 24px;
            font-weight: bold;
        }

        header nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
        }

        header nav a:hover {
            text-decoration: underline;
        }

        .container {
            display: flex;
            background: rgba(255, 255, 255, 0.95);
            margin: 20px auto;
            border-radius: 10px;
            width: 90%;
            max-width: 1000px;
            min-height: 500px;
        }

        .inbox {
            width: 30%;
            border-right: 1px solid #ccc;
            padding: 20px;
            overflow-y: auto;
        }

        .inbox h2 {
            margin-bottom: 20px;
            color: #2b7a0b;
        }

        .inbox ul {
            list-style: none;
        }

        .inbox li {
            padding: 10px;
            border-bottom: 1px solid #ccc;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .inbox li:hover {
            background: #f1f1f1;
        }

        .badge {
            background: red;
            color: white;
            border-radius: 50%;
            padding: 3px 7px;
            font-size: 12px;
        }

        .chat {
            width: 70%;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 10px;
        }

        .message {
            padding: 10px;
            margin: 5px 0;
            border-radius: 8px;
            max-width: 70%;
        }

        .customer {
            background: #e0ffe0;
            align-self: flex-start;
        }

        .vendor {
            background: #cce0ff;
            align-self: flex-end;
        }

        .chat-input {
            display: flex;
            gap: 10px;
        }

        .chat-input input[type="text"] {
            flex: 1;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .chat-input input[type="file"] {
            padding: 5px;
        }

        .chat-input button {
            padding: 10px 15px;
            background: #2b7a0b;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .chat-input button:hover {
            background: #1e5307;
        }

        .links {
            text-align: center;
            margin-top: 15px;
            width: 100%;
        }

        .links a {
            text-decoration: none;
            color: #2b7a0b;
            margin: 0 10px;
        }

        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">🌾 Vendor Messages</div>
        <nav>
            <a href="vendor_dashboard.html">Dashboard</a>
            <a href="vendor_profile.html">Profile</a>
            <a href="index.html">Logout</a>
        </nav>
    </header>

    <div class="container">
        <div class="inbox">
            <h2>Inbox</h2>
            <ul>
                <li>John Doe <span class="badge">2</span></li>
                <li>Mary Smith <span class="badge">1</span></li>
                <li>David Lee</li>
            </ul>
        </div>
        <div class="chat">
            <div class="chat-messages">
                <div class="message customer">Hello, is the handmade basket available?</div>
                <div class="message vendor">Yes, it's in stock.</div>
                <div class="message customer">Great! Can I reserve it?</div>
            </div>
            <div class="chat-input">
                <input type="text" placeholder="Type your message">
                <input type="file" accept="image/*">
                <button>Send</button>
            </div>
            <div class="links">
                <a href="vendor_dashboard.html">Dashboard</a> |
                <a href="vendor_profile.html">Profile</a> |
                <a href="index.html">Logout</a>
            </div>
        </div>
    </div>
</body>

</html>