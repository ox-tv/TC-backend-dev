<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            padding: 50px;
        }

        .header {
            align-items: center;
            background: #2c2d34;
            color: white;
            display: flex;
            justify-content: space-between;
            border-radius: 20px;
            padding: 0 30px;
        }

        .addressTable {
            display: flex;
            margin: 100px 0;
        }

        h2 {
            font-size: 16px;
            margin-bottom: 40px;
        }

        .addressTable > div {
            width: 50%;
        }

        .details header {
            align-items: center;
            background: #2c2d34;
            color: white;
            display: flex;
            justify-content: space-between;
            border-radius: 20px;
            padding: 15px;
        }

        .details header span:nth-child(2) {
            margin-left: -350px;
        }

        @media print {
            .details header span:nth-child(2) {
                margin-left: -150px;
            }
        }

        .details p {
            margin-top: 50px;
        }

        .question {
            align-items: center;
            border-radius: 20px;
            background: #2c2d34;
            color: white;
            display: flex;
            justify-content: space-between;
            margin-top: 80px;
            padding: 15px;
        }

    </style>
</head>
<body>
<header class="header">
    <h1>INVOICE</h1>
    <span>Payment date</span>
</header>
<div class="addressTable">
    <div>
        <h2>Invoice Recipient</h2>
        <p>
            BlockBeast AB <br>
            (todayscrypto.com) <br>
            VAT no: SE559355317401 <br>
            Org no: 559355-3174 <br>
            Transportvägen 12 <br>
            SE-246 42, Löddeköpinge <br>
            SWEDEN
        </p>
    </div>
    <div>
        <h2>Client</h2>
        <p>
            Publishers first and last name <br>
            Company name <br>

            Street address and street no <br>
            Postal code and city <br>
            Country <br>
            VAT number
        </p>
    </div>
</div>

<div class="details">
    <h2>Details</h2>
    <header>
        <span>Content Monetization</span>
        <span>Month</span>
        <span>Amount in USDC</span>
    </header>
    <p>
        The amount (USDC) is sent to client over the Ethereum blockchain to address: <br>
        publisher wallet address
    </p>
</div>

<div class="question">
    <p>
        Do you have questions about your payout? <br>
        Then, do not hesitate to contact us by opening a ticket in your publisher panel.
    </p>
</div>
</body>
</html>