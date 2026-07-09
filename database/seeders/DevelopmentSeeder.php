<?php

namespace Database\Seeders;

use App\Models\DirectoryContact;
use App\Models\EmailCategory;
use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'IT', 'description' => 'Technology and infrastructure issues'],
            ['name' => 'Operations', 'description' => 'Operational and process reports'],
            ['name' => 'Finance', 'description' => 'Budget, compliance, and finance matters'],
            ['name' => 'HR', 'description' => 'People and workplace issues'],
        ];

        foreach ($categories as $category) {
            EmailCategory::query()->firstOrCreate(['name' => $category['name']], $category);
        }

        $contacts = [
            ['display_name' => 'Jane Doe', 'email' => 'jane.doe@org.com', 'department' => 'IT'],
            ['display_name' => 'John Smith', 'email' => 'john.smith@org.com', 'department' => 'Finance'],
            ['display_name' => 'IT Support', 'email' => 'it.support@org.com', 'department' => 'IT'],
            ['display_name' => 'Finance Team', 'email' => 'finance@org.com', 'department' => 'Finance'],
        ];

        foreach ($contacts as $contact) {
            DirectoryContact::query()->updateOrCreate(
                ['email' => $contact['email']],
                array_merge($contact, ['last_synced_at' => now()])
            );
        }

        $recipients = [
            ['name' => 'John Doe', 'shared_mailbox_email' => 'john.doe@org.com', 'department' => 'Engineering', 'role' => 'Engineering Lead'],
            ['name' => 'Sarah Miller', 'shared_mailbox_email' => 'sarah.miller@org.com', 'department' => 'Product', 'role' => 'Product Manager'],
            ['name' => 'IT Support', 'shared_mailbox_email' => 'it.support@org.com', 'department' => 'Engineering', 'role' => 'Support Desk'],
            ['name' => 'Finance Team', 'shared_mailbox_email' => 'finance@org.com', 'department' => 'Human Resources', 'role' => 'Finance Coordinator'],
        ];

        foreach ($recipients as $recipient) {
            \App\Models\Recipient::query()->firstOrCreate(
                ['shared_mailbox_email' => $recipient['shared_mailbox_email']],
                $recipient
            );
        }
    }
}
