<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <meta
        http-equiv="X-UA-Compatible"
        content="IE=edge"
    >
    <title>Kode OTP</title>
    <style>
        /* Reset untuk email clients */
        body,
        table,
        td,
        a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table,
        td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            outline: none;
            text-decoration: none;
        }

        /* Responsive */
        @media screen and (max-width: 600px) {
            .container {
                width: 100% !important;
                max-width: 100% !important;
            }

            .content-padding {
                padding: 24px 20px !important;
            }

            .otp-code {
                font-size: 36px !important;
                letter-spacing: 6px !important;
                padding: 16px !important;
            }

            .header-title {
                font-size: 20px !important;
            }
        }
    </style>
</head>

<body
    style="margin: 0; padding: 0; background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; -webkit-font-smoothing: antialiased;"
>

    <!-- Wrapper Table -->
    <table
        border="0"
        cellpadding="0"
        cellspacing="0"
        width="100%"
        style="background-color: #f8f9fa;"
    >
        <tr>
            <td
                align="center"
                style="padding: 40px 20px;"
            >

                <!-- Main Container -->
                <table
                    border="0"
                    cellpadding="0"
                    cellspacing="0"
                    width="500"
                    class="container"
                    style="max-width: 500px; width: 100%; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border-collapse: collapse;"
                >

                    <!-- Header -->
                    <tr>
                        <td
                            class="content-padding"
                            style="padding: 40px 40px 20px 40px; text-align: center;"
                        >
                            <h2
                                class="header-title"
                                style="margin: 0; color: #1a2332; font-size: 24px; font-weight: 600;"
                            >Kode OTP</h2>
                            <p style="margin: 8px 0 0 0; color: #6c757d; font-size: 15px;">Halo {{ $name }},</p>
                        </td>
                    </tr>

                    <!-- Body Text -->
                    <tr>
                        <td
                            class="content-padding"
                            style="padding: 0 40px 20px 40px; text-align: center;"
                        >
                            <p style="margin: 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                Gunakan kode OTP di bawah ini untuk login ke Buweuk Sipit Academy:
                            </p>
                        </td>
                    </tr>

                    <!-- OTP Code Box -->
                    <tr>
                        <td
                            class="content-padding"
                            style="padding: 0 40px 20px 40px;"
                        >
                            <table
                                border="0"
                                cellpadding="0"
                                cellspacing="0"
                                width="100%"
                            >
                                <tr>
                                    <td
                                        style="background-color: #f8f9fa; border-radius: 8px; padding: 24px; text-align: center;">
                                        <span
                                            class="otp-code"
                                            style="font-size: 48px; font-weight: 900; letter-spacing: 8px; color: #1a2332; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: block;"
                                        >
                                            {{ $otp }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Info -->
                    <tr>
                        <td
                            class="content-padding"
                            style="padding: 0 40px 30px 40px; text-align: center;"
                        >
                            <p style="margin: 0; color: #6c757d; font-size: 14px; line-height: 1.6;">
                                Kode OTP berlaku selama 5 menit.<br>
                                Jika Anda tidak merasa login, abaikan email ini.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 40px 30px 40px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="margin: 0; color: #6c757d; font-size: 12px;">
                                &copy; 2026 Buweuk Sipit Academy. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- End Main Container -->

            </td>
        </tr>
    </table>

</body>

</html>
