@isset($preview)
    <!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
    <html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Document</title>
    </head>

    <body>
@endisset
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

            @isset($company)
                @if($company->agency->isPremium())
                    @if($company->getOriginal('logo'))
                        <img src="{{$company->logo}}" border="0" width="100" alt="HRBlade logo" />
                    @else
                        {{$company->name}}
                    @endif
                @else
                    <img src="{{url('/emails/logo.png')}}" border="0" width="100" alt="HRBlade logo" />
                @endif
            @else
                <img src="{{url('/emails/logo.png')}}" border="0" width="100" alt="HRBlade logo" />
            @endisset

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
                        {!! $content !!}
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
        </td>
    </tr>
</table>
@isset($preview)
</body>
</html>
@endisset