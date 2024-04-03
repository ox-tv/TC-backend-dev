<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PDF Invoice</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            border: 0;
        }

        .tc-print-page{
            page-break-after: always;
            page-break-inside: avoid;
        }

        .green {
            color: #44e900;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            padding: 50px;
        }

        table {
            border-collapse: collapse;
        }

        .header h3 {
            margin-bottom: 5px;
            font-size: 20px;
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

        .thank-you {
            margin: 50px 0 60px;
        }

        .addressTable {
            margin: 70px 0 50px;
        }

        .addressTable td {
            vertical-align: top;
        }

        .addressTable h5{
            font-size: 12px;
            margin-bottom: 15px;
            color: #222;
        }

        .received-address {
            margin-top: 30px;
        }

        .question {
            margin-top: 30px;
        }

        .details h3 {
            margin-bottom: 5px;
            font-size: 20px;
        }

        .details table thead td{
            font-weight: bold;
            font-size: 15px;
            padding-bottom: 20px;
        }

        .details table td:nth-child(1) {
            width: 30%;
        }

        .details table tbody td{
            border-bottom: 2px solid #000;
            padding: 9px 0;
        }

        .details table tfoot .total-earned td{

            padding: 25px 0;
        }

        .details table tfoot .total-share h2{
            font-size: 22px;
        }

        .details header {
            padding: 20px;
            border: 2px solid #000;
            border-radius: 10px;
        }

        .details p {
            margin-top: 50px;
        }
        .bottom-logo{
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            margin: 0 auto;
            max-width: 150px;
        }
        .bottom-logo img{
            width: 150px;
        }
    </style>
</head>
<body>
    @foreach($payouts as $payout)
        <div class="tc-print-page">
            <header class="header">
                <table>
                    <thead>
                    <tr>
                        <td>
                            <h3>INVOICE / REPORT</h3>
                            <span>{{ $payout->monetization->month->format("F - Y") }}</span>
                        </td>
                        <td>
                            <h1><span>Total USDT</span>&nbsp;&nbsp;&nbsp;<span class="green">${{ round($payout->amount, 1) }}</span></h1>

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
                                <h5>Receiver</h5>
                                <p>
                                    BLOCKBEAST AB <br>
                                    Transportvägen 12 <br>
                                    246 42 Löddeköpinge <br>
                                    SK - SWEDEN <br>
                                    VAT no: SE559355317401 <br>
                                </p>
                            </div>

                        </td>
                        <td>
                            <div>
                                <h5>Sender</h5>
                                <p>
                                    {{ $payout->channel->name }}<br>
                                    {{ $payout->payment_details['first_name']?? '-' }} {{ $payout->payment_details['last_name']?? '-' }} <br>
                                    {{ $payout->payment_details['company_name']?? '-' }} <br>
                                    {{ $payout->payment_details['vat_number']?? '-' }} <br>
                                    {{ $payout->payment_details['street_address']?? '-' }} {{ $payout->payment_details['street_number']?? '-' }} <br>
                                    {{ $payout->payment_details['postal_code']?? '-' }} - {{ $payout->payment_details['city']?? '-' }} <br>
                                    {{ $payout->payment_details['country']?? '-' }} <br>
                                </p>
                            </div>
                        </td>
                    </tr>
                    </thead>
                </table>
            </div>

            <div class="thank-you">
                <p>
                    Thank you for being a part of Today's Crypto!
                </p>
            </div>

            <div class="details">
                <h3>REPORT</h3>
                <header>
                    <table>
                        <thead>
                        <tr>
                            <td>
                                <span>STATISTICS</span>
                            </td>
                            <td>
                                <span></span>
                            </td>
{{--                            <td>--}}
{{--                                <span>POINTS</span>--}}
{{--                            </td>--}}
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                <span>Subscriber count (1th)</span><br>
                                <span>HOLD Subscribers</span>
                            </td>
                            <td>
                                <span>{{ round($payout->metrics['subscribers_total']?? 0, 2) }}</span><br>
                                <span>{{ round($payout->metrics['subscribers_hero']?? 0, 2) }}</span>
                            </td>
{{--                            <td>--}}
{{--                                <span>XXXX</span><br>--}}
{{--                                <span>XXXX</span>--}}
{{--                            </td>--}}
                        </tr>
                        <tr>
                            <td>
                                <span>Video views</span>
                            </td>
                            <td>
                                <span>{{ round($payout->metrics['views']?? 0, 2) }}</span>
                            </td>
{{--                            <td>--}}
{{--                                <span>XXXX</span>--}}
{{--                            </td>--}}
                        </tr>
                        <tr>
                            <td>
                                <span>Watch hours</span>
                            </td>
                            <td>
                                <span>{{ round($payout->metrics['watch_times']?? 0, 2)/60/60 }} Hour(s)</span>
                            </td>
{{--                            <td>--}}
{{--                                <span>XXXX</span>--}}
{{--                            </td>--}}
                        </tr>
                        <tr>
                            <td>
                                <span>Video likes</span><br>
                                <span>HOLD likes</span>
                            </td>
                            <td>
                                <span>{{ round($payout->metrics['likes_total']?? 0, 2) }}</span><br>
                                <span>{{ round($payout->metrics['likes_hero']?? 0, 2) }}</span>
                            </td>
{{--                            <td>--}}
{{--                                <span>XXXX</span><br>--}}
{{--                                <span>XXXX</span>--}}
{{--                            </td>--}}
                        </tr>
                        </tbody>
                        <tfoot>
                        <tr class="total-earned">
                            <td>Total points earned</td>
                            <td>{{ round($payout->metrics['points']?? 0, 2) }}</td>
                            <td></td>
                        </tr>
                        <tr class="total-share">
                            <td><h2>Total share in %</h2></td>
                            <td><h2>{{ round($payout->metrics['share']?? 0, 2) }}</h2></td>
                            <td></td>
                        </tr>
                        </tfoot>
                    </table>
                </header>

            </div>

            <div class="received-address">
                <p>
                    Payment will be processed within 10 days. <br>
                    Receiving address {{ $payout->wallet_address }}
                </p>
            </div>

            <div class="question">
                <p>
                    Questions? <br>
                    monetize@todayscrypto.com
                </p>
            </div>

            <div class="bottom-logo">
                <img src="https://cl-dev.todayscrypto.com/assets/images/pdf-invoice-logo.png" alt="">
            </div>
        </div>
    @endforeach

</body>
</html>