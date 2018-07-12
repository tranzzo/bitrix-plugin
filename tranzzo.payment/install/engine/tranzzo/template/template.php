<? if(!empty($params['redirect'])): ?>
    <a href="<?=$params['redirect']?>" class="btn-tranzzo"><?=$params['text_btn']?></a>
    <style>
        .btn-tranzzo{
            background-color: #53a4d8;
            border-color: #53a4d8;
            color: #fff;

            padding: 10px 16px;
            font-size: 18px;
            line-height: 1.3333333;
            border-radius: 6px;

            display: inline-block;
            margin-bottom: 0;
            font-weight: normal;;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            touch-action: manipulation;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .btn-tranzzo:hover{
            background-color: #106eab;
            color: #fff;
            text-shadow: none !important;
            -webkit-box-shadow: none !important;
            -moz-box-shadow: none !important;
            box-shadow: none !important;
            outline: none !important;
            text-decoration: none;
        }
    </style>
<? else: ?>
    <p style="font-size: 20px"><?=$params['msg']?></p>

    <p style="font-size: 15px"><i><?=$params['error']?></i></p>
<? endif; ?>