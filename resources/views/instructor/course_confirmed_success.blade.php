<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="{{url('/')}}/images/favicon.ico">
    <title>Element3 - Email Verification</title>
</head>
<body>
<div class="layout--full-page">
    <div class="h-screen flex w-full bg-gray webpaymentPage SuccessPage mt-10 mb-10 mm-0">
        <div class="vx-col mx-auto vs-lg-9 self-center login-page">
            <div class="vx-card">
                <!---->
                <div class="vx-card__collapsible-content vs-con-loading__container">
                    <div>
                        <div class="vx-row">
                            <div class="vx-col w-full mx-auto self-center">
                                <div class="p-20 bg-white borderradius20">
                                    <div>
                                        <div class="vx-col lg:block lg:w-1/3 mx-auto mt-0 self-center">
                                        <img src="{{url('/')}}/images/logo-element3.png" width="200" alt="Element3 Logo" style="display:block;margin-bottom:50px;" class="logo-img mx-auto" />
                                        <?php if($data==2) { ?>
                                            <img src="{{url('/')}}/images/checkmark.gif" alt="success" width="130" class="mx-auto">
                                            <?php }else if($data==1 || $data==0){ ?>
                                                <img src="{{url('/')}}/images/crossmark.gif" alt="error" width="130" class="mx-auto">
                                            <?php } ?>
                                        </div>
                                        <div class="otherMsgDetail">
                                            <?php if($data==2) { ?>
                                            <h2 class="text-center grayBigTextTransparent">Success</h2>
                                            <?php }else if($data==1 || $data==0){ ?>
                                                <h2 class="text-center grayBigTextTransparent">Error</h2>
                                            <?php } ?>
                                            <div class="otherSubDetail">
                                                <?php if($data==2) { ?>
                                                <h3 class="text-center"><b>Your course has been confirmed successfully!</b></h3>
                                                <?php }else if($data==1){ ?>
                                                    <h3 class="text-center"><b>Your course has been already confirmed!</b></h3>
                                                <?php }else if($data==0){ ?>
                                                    <h3 class="text-center"><b>Your course confirm token has been expired!</b></h3>
                                                <?php } ?>
                                                <?php if($data==2 || $data==1) { ?>
                                                <p class="mt-5 text-center">You can now view course detail to the element3 application.</p>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!---->
                    <!---->
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

<style>
/* General Css */
body, .vx-card__body label, .vs-select--label {
    color: #333333;
    font-weight: 500;
}
body {
    background: #fff;
    font-family: "Open Sans", sans-serif;
}
.h-screen {
    height: 100vh !important;
}
.flex {
    display: -webkit-box !important;
    display: -ms-flexbox !important;
    display: flex !important;
}
.bg-white {
    background-color: #fff !important;
}
.w-full {
    width: 100% !important;
}
.vx-row>.vx-col {
    padding: 0 1rem;
}
.p-20 {
    padding: 5rem !important;
}
.mt-0 {
    margin-top: 0 !important;
}
.mx-auto {
    margin-left: auto !important;
    margin-right: auto !important;
}
.vx-card .vx-card__collapsible-content img {
    display: block;
}
.mb-10 {
    margin-bottom: 2.5rem !important;
}
.mt-3 {
    margin-top: .75rem !important;
}
.borderradius20 {
    border-radius: 20px;
}
.vs-lg-9 {
    width: 75% !important;
}
.vx-card {
    width: 100%;
    background: #fff;
    border-radius: .5rem;
    display: block;
    -webkit-box-shadow: 0px 4px 25px 0px rgba(0, 0, 0, .1);
    box-shadow: 0px 4px 25px 0px rgba(0, 0, 0, .1);
    position: relative;
    -webkit-transition: all .3s ease-in-out;
    transition: all .3s ease-in-out;
}  
.vx-col .vx-card {
    min-height: 100%;
    position: relative;
}
.text-center {
    text-align: center !important;
}
.grayBigTextTransparent {
    font-size: 8rem;
    color: #f6f6f6;
    font-weight: bolder;
    margin: 0;
    line-height: normal;
} 
.otherSubDetail {
    margin-top: -30px;
}
h3, .h3 {
    font-size: 21.14px;
}
.mt-5 {
    margin-top: 1.25rem !important;
}
.self-center {
    -ms-flex-item-align: center !important;
    align-self: center !important;
}
.vx-card .vx-card__collapsible-content img {
    display: block;
}   
.mt-0 {
    margin-top: 0 !important;
}
.borderradius20 {
    border-radius: 20px;
}
.vx-row {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -ms-flex-wrap: wrap;
    flex-wrap: wrap;
    margin: 0 -1rem;
}

/* Responsive Css */
@media only screen and (max-width: 1400px) and (min-width: 768px)
{
    .webpaymentPage.h-screen {
    height: 100% !important;
}
}
@media only screen and (max-width: 992px)
{
    .grayBigTextTransparent {
    font-size: 5rem;
    }
    /* .lg\:w-1\/3 {
        width: 33.33333% !important;
    } */
    .lg\:block {
        display: block !important;
    }
}


@media only screen and (max-width: 767px)
{
    .grayBigTextTransparent {
    font-size: 3rem;
}
.webpaymentPage.SuccessPage .p-20 {
    padding: 3rem !important;
}
.vs-lg-9 {
    width: 100% !important;
}
.flex.w-full {
    -ms-flex-wrap: wrap;
    flex-wrap: wrap;
}
.webpaymentPage.mm-0 {
    margin: 0 !important;
}
.layout--full-page .webpaymentPage .vx-row {
    margin: 0 !important;
}
.webpaymentPage.SuccessPage .vx-card {
    -webkit-box-shadow: none;
    box-shadow: none;
}

}
</style>
<!-- <h1 style="color:green">{{$data}}</h1> -->