<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Order Notification</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            background-color: #f8fafc;
            padding: 40px 20px;
            box-sizing: border-box;
        }
        .container {
            max-width: 650px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }
        .header {
            background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
            color: #ffffff;
            padding: 30px 25px;
        }
        .content {
            padding: 40px;
        }
        .greeting {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 10px;
        }
        .intro-text {
            font-size: 14px;
            color: #475569;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .details-box {
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 24px;
            margin-bottom: 30px;
        }
        .details-title {
            font-size: 11px;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 8px;
        }
        .priority-badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 9px;
            font-weight: 800;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .priority-urgent { background-color: #ffe4e6; color: #b91c1c; }
        .priority-standard { background-color: #fef3c7; color: #d97706; }
        .priority-low { background-color: #dbeafe; color: #1d4ed8; }

        .role-section {
            border-top: 1px dashed #cbd5e1;
            padding-top: 25px;
            margin-top: 25px;
        }
        .btn-container {
            text-align: center;
            margin: 30px 0 10px 0;
        }
        .btn {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 30px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 5px;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #1d4ed8;
        }
        .pic-list {
            margin: 15px 0 0 0;
            padding-left: 20px;
        }
        .pic-list li {
            font-size: 13px;
            color: #334155;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 24px 40px;
            text-align: center;
        }
        .footer p {
            margin: 0;
            font-size: 11px;
            color: #94a3b8;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <!-- Header -->
            <div class="header" style="text-align: center;">
                <h1 style="margin: 0; font-size: 22px; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase; color: #ffffff; line-height: 1.2;">Work Order Document</h1>
                <p style="margin: 6px 0 0 0; font-size: 11px; color: #93c5fd; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; line-height: 1.2;">PT Summit Adyawinsa Indonesia</p>
            </div>

            <!-- Content -->
            <div class="content">
                <div class="greeting">Dear Mr./Ms. {{ $recipientName }},</div>
                
                @if($role === 'approver')
                    <p class="intro-text">
                        We would like to inform you that a new Work Order (SPK) has been submitted and is currently pending your formal approval. Please find the document details outlined below.
                    </p>
                @else
                    <p class="intro-text">
                        You have been assigned as the PIC / Department representative responsible for executing processes on the following newly submitted Work Order.
                    </p>
                @endif

                <!-- Details Box -->
                <div class="details-box">
                    <div class="details-title">Work Order Information</div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="width: 32%; padding: 8px 0; font-size: 13px; color: #64748b; font-weight: 600; vertical-align: top; line-height: 1.4;">WO Number</td>
                            <td style="width: 3%; padding: 8px 0; font-size: 13px; color: #64748b; font-weight: 600; vertical-align: top; text-align: center; line-height: 1.4;">:</td>
                            <td style="width: 65%; padding: 8px 0; font-size: 13px; color: #0f172a; font-weight: 700; vertical-align: top; line-height: 1.4; word-break: break-all;">{{ $workOrder->wo_number }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-size: 13px; color: #64748b; font-weight: 600; vertical-align: top; line-height: 1.4;">Inquiry No</td>
                            <td style="padding: 8px 0; font-size: 13px; color: #64748b; font-weight: 600; vertical-align: top; text-align: center; line-height: 1.4;">:</td>
                            <td style="padding: 8px 0; font-size: 13px; color: #0f172a; font-weight: 700; vertical-align: top; line-height: 1.4;">{{ $workOrder->inquiry->inquiry_no ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-size: 13px; color: #64748b; font-weight: 600; vertical-align: top; line-height: 1.4;">Project Model</td>
                            <td style="padding: 8px 0; font-size: 13px; color: #64748b; font-weight: 600; vertical-align: top; text-align: center; line-height: 1.4;">:</td>
                            <td style="padding: 8px 0; font-size: 13px; color: #0f172a; font-weight: 700; vertical-align: top; line-height: 1.4;">{{ $workOrder->inquiry->project_name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-size: 13px; color: #64748b; font-weight: 600; vertical-align: top; line-height: 1.4;">Part Items Count</td>
                            <td style="padding: 8px 0; font-size: 13px; color: #64748b; font-weight: 600; vertical-align: top; text-align: center; line-height: 1.4;">:</td>
                            <td style="padding: 8px 0; font-size: 13px; color: #0f172a; font-weight: 700; vertical-align: top; line-height: 1.4;">{{ $workOrder->products->count() }} item(s)</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-size: 13px; color: #64748b; font-weight: 600; vertical-align: top; line-height: 1.4;">Priority</td>
                            <td style="padding: 8px 0; font-size: 13px; color: #64748b; font-weight: 600; vertical-align: top; text-align: center; line-height: 1.4;">:</td>
                            <td style="padding: 8px 0; font-size: 13px; vertical-align: top; line-height: 1.4;">
                                @if($workOrder->priority === 'URGENT')
                                    <span class="priority-badge priority-urgent">URGENT</span>
                                @elseif($workOrder->priority === 'STANDARD')
                                    <span class="priority-badge priority-standard">STANDARD</span>
                                @else
                                    <span class="priority-badge priority-low">{{ $workOrder->priority }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-size: 13px; color: #64748b; font-weight: 600; vertical-align: top; line-height: 1.4;">Due Date (Plan)</td>
                            <td style="padding: 8px 0; font-size: 13px; color: #64748b; font-weight: 600; vertical-align: top; text-align: center; line-height: 1.4;">:</td>
                            <td style="padding: 8px 0; font-size: 13px; color: #0f172a; font-weight: 700; vertical-align: top; line-height: 1.4;">{{ $workOrder->due_date_plan ? $workOrder->due_date_plan->format('d-M-Y') : '—' }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-size: 13px; color: #64748b; font-weight: 600; vertical-align: top; line-height: 1.4;">Prepared By</td>
                            <td style="padding: 8px 0; font-size: 13px; color: #64748b; font-weight: 600; vertical-align: top; text-align: center; line-height: 1.4;">:</td>
                            <td style="padding: 8px 0; font-size: 13px; color: #0f172a; font-weight: 700; vertical-align: top; line-height: 1.4;">{{ $workOrder->created_by }}</td>
                        </tr>
                    </table>
                </div>

                <!-- Role Specific Sections -->
                <div class="role-section">
                    @if($role === 'approver')
                        <div class="greeting" style="font-size:14px; color:#1e3a8a;">Action Required</div>
                        <p class="intro-text" style="font-size:13px; margin-bottom:15px;">
                            Please access the Promise Portal to review the detailed specification and perform approval/sign-off action.
                        </p>
                        <div class="btn-container">
                            <a href="{{ route('management.work-order.approval-inbox', ['select' => $workOrder->hashed_id]) }}" class="btn">Open Inbox &amp; Review</a>
                        </div>
                    @else
                        <div class="greeting" style="font-size:14px; color:#1e3a8a;">Your Assigned Processes</div>
                        <p class="intro-text" style="font-size:13px; margin-bottom:10px;">
                            You are assigned to the checklist process(es) listed below. Please monitor and update the progress in the portal.
                        </p>
                        <ul class="pic-list">
                            @foreach($assignedProcesses as $processName)
                                <li>{{ $processName }}</li>
                            @endforeach
                        </ul>
                        <div class="btn-container">
                            <a href="{{ route('management.work-order.approval-inbox', ['select' => $workOrder->hashed_id]) }}" class="btn">Open Work Order Page</a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p><strong>PT Summit Adyawinsa Indonesia</strong><br>Jl. Pangkal Perjuangan No. 98, Karawang, Jawa Barat</p>
                <p style="margin-top: 12px; font-size: 10px; color: #94a3b8;">This is an auto-generated notification email from the Promise Management System. Please do not reply directly to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>
