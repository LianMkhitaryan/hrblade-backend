{{--<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">--}}
{{--<html xmlns="http://www.w3.org/1999/xhtml">--}}
{{--<head>--}}
{{--<meta name="viewport" content="width=device-width, initial-scale=1.0" />--}}
{{--<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />--}}
{{--<meta name="color-scheme" content="light">--}}
{{--<meta name="supported-color-schemes" content="light">--}}
{{--</head>--}}
{{--<body>--}}
{{--<style>--}}
{{--@media only screen and (max-width: 600px) {--}}
{{--.inner-body {--}}
{{--width: 100% !important;--}}
{{--}--}}

{{--.footer {--}}
{{--width: 100% !important;--}}
{{--}--}}
{{--}--}}

{{--@media only screen and (max-width: 500px) {--}}
{{--.button {--}}
{{--width: 100% !important;--}}
{{--}--}}
{{--}--}}
{{--</style>--}}

{{--<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">--}}
{{--<tr>--}}
{{--<td align="center">--}}
{{--<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">--}}
{{--{{ $header ?? '' }}--}}

{{--<!-- Email Body -->--}}
{{--<tr>--}}
{{--<td class="body" width="100%" cellpadding="0" cellspacing="0">--}}
{{--<table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">--}}
{{--<!-- Body content -->--}}
{{--<tr>--}}
{{--<td class="content-cell">--}}
{{--{{ Illuminate\Mail\Markdown::parse($slot) }}--}}

{{--{{ $subcopy ?? '' }}--}}
{{--</td>--}}
{{--</tr>--}}
{{--</table>--}}
{{--</td>--}}
{{--</tr>--}}

{{--{{ $footer ?? '' }}--}}
{{--</table>--}}
{{--</td>--}}
{{--</tr>--}}
{{--</table>--}}
{{--</body>--}}
{{--</html>--}}


    <!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
    <html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Document</title>
    </head>

    <body>

    <table
            border="0"
            cellpadding="0"
            cellspacing="0"
            style="
        background-attachment: scroll;
        width: 100%;
        margin-top: 0;
        margin-bottom: 0;
        margin-right: 0;
        margin-left: 0;
        padding-top: 40px;
        padding-bottom: 40px;
        padding-right: 20px;
        padding-left: 20px;
        text-align: center;
        background-color: #f9f9fa;
        background-image: none;
        background-repeat: repeat;
        background-position: top left;
      "
    >
        <tr>
            <td
                    style="
            padding-top: 40px;
            padding-bottom: 40px;
            padding-right: 0px;
            padding-left: 0px;
          "
            >
                <img src="{{url('/emails/logo.png')}}" border="0" width="100" alt="HRBlade logo" />
            </td>
        </tr>

        <tr>
            <td>
                <table
                        style="
              background-attachment: scroll;
              width: 100%;
              max-width: 600px;
              margin-top: 0;
              margin-bottom: 0;
              margin-right: auto;
              margin-left: auto;
              padding-top: 0;
              padding-bottom: 0;
              padding-right: 0;
              padding-left: 0;
              text-align: left;
              border-radius: 5px;
              background-color: #ffffff;
              background-image: none;
              background-repeat: repeat;
              background-position: top left;
            "
                >
                    <tr>
                        <td
                                style="
                  padding-top: 20px;
                  padding-bottom: 20px;
                  padding-right: 50px;
                  padding-left: 50px;
                "
                        >
                            {{ Illuminate\Mail\Markdown::parse($slot) }}

                            {{ $subcopy ?? '' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td
                    style="
            padding-top: 40px;
            padding-bottom: 40px;
            padding-right: 0px;
            padding-left: 0px;
          "
            >
                <img src="{{url('/emails/logo-gray.png')}}" border="0" width="60" alt="HRBlade logo" />
                {{ $footer ?? '' }}
            </td>
        </tr>
    </table>
    </body>
    </html>

