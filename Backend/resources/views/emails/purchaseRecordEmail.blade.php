<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ __('web.procurement_notice') }}</title>
</head>
<body>
<P>{{ __('web.procurement_information') }}.</P>
<p style="FONT-SIZE: 14px; FONT-WEIGHT: bold; COLOR: #f90">{{ __('web.purchasing_name') }}： {{ $purchaseInfo['username'] }}</p>
<p style="FONT-SIZE: 14px; FONT-WEIGHT: bold; COLOR: #f90">{{ __('web.purchasing_products') }}： {{ $purchaseInfo['product_name'] }}</p>
<p style="FONT-SIZE: 14px; FONT-WEIGHT: bold; COLOR: #f90">{{ __('web.purchasing_products_specs') }}： {{ $purchaseInfo['product_specs'] }}</p>
<p style="FONT-SIZE: 14px; FONT-WEIGHT: bold; COLOR: #f90">{{ __('web.purchase_quantity') }}： {{ $purchaseInfo['purchase_number'] }}</p>
<p style="FONT-SIZE: 14px; FONT-WEIGHT: bold; COLOR: #f90">{{ __('web.procurement_remarks') }}： {{ $purchaseInfo['purchase_remarks'] }}</p>
<DIV class=footer style="MARGIN-TOP: 30px">
    <P></P>
</DIV>
<DIV class=tip style="COLOR: #cccccc; TEXT-ALIGN: center">{{ __('web.do_not_reply') }}</DIV>
</body>
</html>
