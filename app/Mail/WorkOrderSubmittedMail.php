<?php

namespace App\Mail;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WorkOrderSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $workOrder;
    public $role;
    public $recipientName;
    public $assignedProcesses;

    /**
     * Create a new message instance.
     */
    public function __construct(WorkOrder $workOrder, string $role, string $recipientName, array $assignedProcesses = [])
    {
        $this->workOrder = $workOrder;
        $this->role = $role;
        $this->recipientName = $recipientName;
        $this->assignedProcesses = $assignedProcesses;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->role === 'approver' 
            ? 'Action Required: Work Order Approval Needed - ' . $this->workOrder->wo_number
            : 'Notification: Assigned to Work Order - ' . $this->workOrder->wo_number;

        return $this->subject($subject)
                    ->withSymfonyMessage(function ($message) {
                        $message->getHeaders()->addTextHeader('X-Priority', '1');
                        $message->getHeaders()->addTextHeader('X-MSMail-Priority', 'High');
                        $message->getHeaders()->addTextHeader('Importance', 'high');
                    })
                    ->view('emails.work-order-submitted');
    }
}
