<?php

return [
    // Candidate Portal Invitation
    'candidate_invitation' => [
        'subject' => 'Invitation to Join Our Portal - Unlock Exciting Opportunities!',
        'greeting' => 'Dear :name,',
        'intro' => 'We hope this email finds you well. We are thrilled to extend an exclusive invitation to you to join our dedicated candidate portal, where a world of exciting opportunities and possibilities awaits.',
        'value_proposition' => 'At :company, we value the unique skills, experiences, and potential that each candidate brings to the table. Your application and interview process have left a lasting impression on us, and we believe that you have the qualities that align perfectly with our mission and values.',
        'what_to_expect' => 'Here\'s what you can expect by joining our portal:',
        'tailored_jobs' => '<strong>Tailored Job Opportunities</strong>: Our portal will provide you with a personalized job-matching experience. You\'ll receive job recommendations that align with your skills, experience, and career aspirations.',
        'application_tracking' => '<strong>Application Tracking</strong>: Easily keep track of your application status, interview updates, and hiring progress for positions you\'ve applied to within our organization.',
        'company_insights' => '<strong>Company Insights</strong>: Gain a deeper understanding of our culture, values, and the teams you\'ll potentially be a part of. Get a behind-the-scenes look at what it\'s like to work at :company.',
        'community' => '<strong>Community Engagement</strong>: Connect with fellow candidates, employees, and industry professionals through discussion forums, networking events, and knowledge-sharing opportunities.',
        'get_started' => 'To get started, simply click on the link below to create your portal account:',
        'sign_up' => 'Sign Up',
        'same_email' => 'Please use the same email address that you used during your application process to ensure seamless access to your candidate profile.',
        'talent_community' => 'We are excited to have you as a part of our talent community and to explore the possibilities of you joining our :company family.',
        'great_talent' => 'Thank you for considering this invitation. We believe that great opportunities begin with great talent, and you\'re a testament to that belief.',
        'see_you' => 'We look forward to seeing you on our portal and, hopefully, as a part of our dynamic team.',
        'regards' => 'Regards,',
    ],

    // System User Invitation
    'system_user_invitation' => [
        'subject' => 'Welcome to our System - Complete Your Registration',
        'greeting' => 'Dear :name,',
        'welcome' => 'We are delighted to welcome you to our system! To complete your registration and ensure the security of your account, please follow these simple steps:',
        'verify_email' => '<strong>Verify Your Email Address:</strong> Click on the following link to verify your email address: <a href=\':link\'>Verify and Create Account</a>',
        'create_password' => '<strong>Create Your Password:</strong> After email verification, you will be directed to set your password. Your password must meet the following criteria:',
        'password_criteria' => '<ul><li>At least 8 characters</li><li>A combination of uppercase and lowercase letters</li><li>At least one number</li><li>At least one special character</li></ul>',
        'access_system' => '<strong>Access the System:</strong> Once your password is created, you will have full access to our system using your registered email and the newly created password.',
        'verify_create_action' => 'Verify and Create Account',
        'support' => 'If you encounter any difficulties during the registration process or have any questions, please do not hesitate to reach out to our support team.',
        'thank_you' => 'Thank you for choosing to be a part of our system. We look forward to having you on board and working together to achieve success.',
        'regards' => 'Regards,',
    ],

    // Welcome System User
    'welcome_system_user' => [
        'subject' => 'Welcome to Our System - Verification & Registration Completed',
        'greeting' => 'Dear :name,',
        'registration_complete' => 'We are pleased to inform you that your registration has been successfully completed, and your account is now fully active in our system.',
        'login_info' => 'You can now log in with your registered email address and the password you created during the registration process. If you ever forget your password, you can use the "Forgot Password" feature on the login page to reset it.',
        'login_now' => 'Login Now!',
        'support' => 'If you have any questions or need assistance as you explore the system, please feel free to contact our support team.',
        'thank_you' => 'Thank you for choosing our system, and we look forward to seeing the positive impact of your contributions.',
        'regards' => 'Regards,',
    ],

    // Interview Scheduled
    'interview_scheduled' => [
        'subject' => 'Interview Scheduled - :job at :company',
        'greeting' => 'Dear :name,',
        'intro' => 'We are pleased to inform you that an interview has been scheduled for the **:job** position at :company.',
        'details_heading' => '**Interview Details:**',
        'date_time' => '- **Date & Time:** :datetime',
        'duration' => '- **Duration:** :duration minutes',
        'subject_line' => '- **Subject:** :title',
        'join_info' => 'You will be able to join the video interview from your Candidate Portal when the interview time arrives.',
        'view_interviews' => 'View My Interviews',
        'prepare' => 'Please ensure you have a stable internet connection and a working camera/microphone.',
        'looking_forward' => 'Thank you for your interest, and we look forward to speaking with you!',
    ],

    // New Candidate Portal Account
    'new_candidate_account' => [
        'subject' => 'Your Candidate Portal Account is Ready!',
        'greeting' => 'Dear :name',
        'intro' => 'We\'re thrilled to inform you that your candidate portal account has been successfully created. Welcome to our platform! This email is to confirm the successful creation of your account using the invitation you received.',
        'key_details' => 'Here are a few key details:',
        'account_info' => '<strong>Candidate Portal Account Information:</strong>',
        'email_address' => 'Email Address: :email',
        'getting_started_heading' => '<strong>Getting Started:</strong>',
        'getting_started_intro' => 'You can now log in to your account and start exploring the features and opportunities offered by our portal. Here\'s how:',
        'step_login_page' => '1. Go to our candidate portal login page: :link',
        'step_use_credentials' => '2. Use the provided email and the password you\'ve created',
        'step_dashboard' => '3. After logging in, you can access your personalized candidate dashboard, see your applied jobs status, and many more.',
        'important_note' => '<strong>Important Note:</strong>',
        'keep_credentials_safe' => '<ul><li>Keep your login credentials safe and do not share them with others.</li></ul>',
        'support' => 'If you encounter any issues during the registration or need assistance, don\'t hesitate to reach out to our support team.',
        'excited' => 'We\'re excited to have you as part of our candidate community. Explore job listings, update your profile, and make the most of our portal to advance your career.',
        'thank_you' => 'Thank you for choosing us, and best of luck in your journey!',
        'regards' => 'Regards,',
    ],

    // New Matching Jobs Notification
    'new_matching_jobs' => [
        'subject' => 'New Job Matches Found — :company',
        'greeting' => 'Hello :name,',
        'intro' => 'Great news! We\'ve found new job openings that match your profile:',
        'match_format' => ':score% match',
        'salary' => 'Salary: :salary',
        'remote' => 'Remote',
        'more_jobs' => 'And :count more matching jobs!',
        'login_info' => 'Log in to your candidate portal to view all recommended jobs and their detailed match analysis.',
        'view_recommended' => 'View Recommended Jobs',
        'regards' => 'Best regards,',
    ],
];
