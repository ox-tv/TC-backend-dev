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
            background: #2c2d34;
            color: white;
            border-radius: 20px;
            padding: 0 30px;
        }

        table {
            width: 100%;
        }

        header.header table td:first-child {
            text-align: left;
        }

        header.header table td:nth-child(2) {
            text-align: right;
        }

        .details header table td:nth-child(1) {
            width: 30%;
        }

        .details header table td:nth-child(3) {
            text-align: right;
        }

        .addressTable {
            margin: 100px 0;
        }

        .addressTable td {
            vertical-align: top;
        }

        h2 {
            font-size: 16px;
            margin-bottom: 40px;
            margin-top: 0;
        }

        .details header {
            background: #2c2d34;
            color: white;
            border-radius: 20px;
            padding: 15px;
        }

        .details p {
            margin-top: 50px;
        }

        .question {
            border-radius: 20px;
            background: #2c2d34;
            color: white;
            margin-top: 70px;
            padding: 15px;
        }

    </style>
</head>
<body>
<header class="header">
    <table>
        <thead>
        <tr>
            <td>
                <h1>INVOICE</h1>

            </td>
            <td>
                <span>{{ $earning->transaction->updated_at }}</span>

            </td>
        </tr>
        </thead>
    </table>
</header>
<div class="addressTable">
    <table>
        <thead>
        <tr>
            <td>
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

            </td>
            <td>
                <div>
                    <h2>Client</h2>
                    <p>
                        {{ $earning->client_information['first_name'] }} {{ $earning->client_information['last_name'] }}<br>

                        @if(!empty($earning->client_information['company_name']))
                        {{ $earning->client_information['company_name'] }} <br>
                        @endif

                        {{ $earning->client_information['street_address'] }} {{ $earning->client_information['street_number'] }} <br>
                        {{ $earning->client_information['postal_code'] }}, {{ $earning->client_information['city'] }} <br>
                        {{ strtoupper($earning->client_information['country']) }} <br>
                        @if(!empty($earning->client_information['vat_number']))
                            VAT no: {{ $earning->client_information['vat_number'] }} <br>
                        @endif
                    </p>
                </div>
            </td>
        </tr>
        </thead>
    </table>
</div>

<div class="details">
    <h2>Details</h2>
    <header>
        <table>
            <thead>
            <tr>
                <td>
                    <span>Content Monetization</span>
                </td>
                <td>
                    <span>{{ $earning->date }}</span>
                </td>
                <td>
                    <span>{{ $earning->amount }} USDC</span>
                </td>
            </tr>
            </thead>
        </table>
    </header>
    <p>
        The amount (USDC) is sent to client over the Ethereum blockchain to address: <br>
        {{ $earning->client_information['eth_address'] }}
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