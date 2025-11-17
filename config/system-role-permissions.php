<?php

return [
    'default-permission' => [
        'title' => 'Default Permissions',
        'sections' => [
            [
                'title' => 'Currency',
                'routes' => [
                    [
                        'title' => 'Currency List',
                        'route' => 'admin.currency.index',
                    ],

                    [
                        'title' => 'Edit',
                        'route' => 'admin.currency.edit',
                    ],
                    [
                        'title' => 'Update',
                        'route' => 'admin.currency.update',
                    ],
                ],
            ],
            [
                'title' => 'Fees & Charges',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.trx.settings.index',
                    ],
                    [
                        'title' => 'Charges Update',
                        'route' => 'admin.trx.settings.charges.update',
                    ],
                ],
            ],
            [
                'title' => 'Subscriber',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.subscriber.index',
                    ],
                    [
                        'title' => 'Send Mail',
                        'route' => 'admin.subscriber.send.mail',
                    ],
                ],
            ],
            [
                'title'  => 'Car Types',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.car.types.index',
                    ],
                    [
                        'title' => 'Store',
                        'route' => 'admin.car.types.store',
                    ],
                    [
                        'title' => 'Status Update',
                        'route' => 'admin.car.types.status.update',
                    ],
                    [
                        'title' => 'Update',
                        'route' => 'admin.car.types.update',
                    ],
                    [
                        'title' => 'Delete',
                        'route' => 'admin.car.types.delete',
                    ],
                ],
            ],
            [
                'title'  => 'Car Area',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.car.area.index',
                    ],
                    [
                        'title' => 'Create',
                        'route' => 'admin.car.area.create',
                    ],
                    [
                        'title' => 'Store',
                        'route' => 'admin.car.area.store',
                    ],
                    [
                        'title' => 'Edit',
                        'route' => 'admin.car.area.edit',
                    ],
                    [
                        'title' => 'Update',
                        'route' => 'admin.car.area.update',
                    ],
                    [
                        'title' => 'Status Update',
                        'route' => 'admin.car.area.status.update',
                    ],
                    [
                        'title' => 'Delete',
                        'route' => 'admin.car.area.delete',
                    ],
                ],
            ],
            [
                'title'  => 'Car',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.car.index',
                    ],
                    [
                        'title' => 'Approval Update',
                        'route' => 'admin.car.approval.update',
                    ],
                ],
            ],
            [
                'title' => 'Payment Methods',
                'routes' => [
                    [
                        'title' => 'Create',
                        'route' => 'admin.payment.gateway.create',
                    ],
                    [
                        'title' => 'Store',
                        'route' => 'admin.payment.gateway.store',
                    ],
                    [
                        'title' => 'View',
                        'route' => 'admin.payment.gateway.view',
                    ],
                    [
                        'title' => 'Edit',
                        'route' => 'admin.payment.gateway.edit',
                    ],
                    [
                        'title' => 'Update',
                        'route' => 'admin.payment.gateway.update',
                    ],
                    [
                        'title' => 'Status Update',
                        'route' => 'admin.payment.gateway.status.update',
                    ],
                    [
                        'title' => 'Remove',
                        'route' => 'admin.payment.gateway.remove',
                    ],
                ],
            ],
        ],
    ],
    'interface-permission' => [
        'title' => 'Interface Panel Permissions',
        'sections' => [
            [
                'title' => 'User Care',
                'routes' => [
                    [
                        'title' => 'User List',
                        'route' => 'admin.users.index',
                    ],
                    [
                        'title' => 'Active Users',
                        'route' => 'admin.users.active',
                    ],
                    [
                        'title' => 'Create Users',
                        'route' => 'admin.users.create',
                    ],
                    [
                        'title' => 'Store Users',
                        'route' => 'admin.users.store',
                    ],
                    [
                        'title' => 'Banned Users',
                        'route' => 'admin.users.banned',
                    ],
                    [
                        'title' => 'Email Unverified',
                        'route' => 'admin.users.email.unverified',
                    ],
                    [
                        'title' => 'Email To Users',
                        'route' => 'admin.users.email.users',
                    ],
                    [
                        'title' => 'Send Mail To Users',
                        'route' => 'admin.users.email.users.send',
                    ],
                    [
                        'title' => 'User Details',
                        'route' => 'admin.users.details',
                    ],
                    [
                        'title' => 'User Details Update',
                        'route' => 'admin.users.details.update',
                    ],
                    [
                        'title' => 'Login Logs',
                        'route' => 'admin.users.login.logs',
                    ],
                    [
                        'title' => 'Mail Logs',
                        'route' => 'admin.users.mail.logs',
                    ],
                    [
                        'title' => 'Send Mail',
                        'route' => 'admin.users.send.mail',
                    ],
                    [
                        'title' => 'Login as Member',
                        'route' => 'admin.users.login.as.member',
                    ],
                    [
                        'title' => 'Wallet Balance Update',
                        'route' => 'admin.users.wallet.balance.update',
                    ],
                    [
                        'title' => 'User Search',
                        'route' => 'admin.users.search',
                    ],
                ],
            ],
            [
                'title'  => 'Vendor Care',
                'routes' => [
                    [
                        'title' => 'Vendor Index',
                        'route' => 'admin.vendor.index',
                    ],
                    [
                        'title' => 'Vendor Active',
                        'route' => 'admin.vendor.active',
                    ],
                    [
                        'title' => 'Vendor Banned',
                        'route' => 'admin.vendor.banned',
                    ],
                    [
                        'title' => 'Vendor Email Unverified',
                        'route' => 'admin.vendor.email.unverified',
                    ],
                    [
                        'title' => 'Vendor SMS Unverified',
                        'route' => 'admin.vendor.sms.unverified',
                    ],
                    [
                        'title' => 'Vendor KYC Unverified',
                        'route' => 'admin.vendor.kyc.unverified',
                    ],
                    [
                        'title' => 'Vendor KYC Details',
                        'route' => 'admin.vendor.kyc.details',
                    ],
                    [
                        'title' => 'Vendor Email All Users',
                        'route' => 'admin.vendor.email.users',
                    ],
                    [
                        'title' => 'Vendor Email Users Send',
                        'route' => 'admin.vendor.email.users.send',
                    ],
                    [
                        'title' => 'Vendor Details',
                        'route' => 'admin.vendor.details',
                    ],
                    [
                        'title' => 'Vendor Details Update',
                        'route' => 'admin.vendor.details.update',
                    ],
                    [
                        'title' => 'Vendor Login Logs',
                        'route' => 'admin.vendor.login.logs',
                    ],
                    [
                        'title' => 'Vendor Mail Logs',
                        'route' => 'admin.vendor.mail.logs',
                    ],
                    [
                        'title' => 'Vendor Send Mail',
                        'route' => 'admin.vendor.send.mail',
                    ],
                    [
                        'title' => 'Vendor Login As Member',
                        'route' => 'admin.vendor.login.as.member',
                    ],
                    [
                        'title' => 'Vendor KYC Approve',
                        'route' => 'admin.vendor.kyc.approve',
                    ],
                    [
                        'title' => 'Vendor KYC Reject',
                        'route' => 'admin.vendor.kyc.reject',
                    ],
                    [
                        'title' => 'Vendor Search',
                        'route' => 'admin.vendor.search',
                    ],
                    [
                        'title' => 'Vendor Wallet Balance Update',
                        'route' => 'admin.vendor.wallet.balance.update',
                    ],
                    [
                        'title' => 'Vendor Create',
                        'route' => 'admin.vendor.create',
                    ],
                    [
                        'title' => 'Vendor Store',
                        'route' => 'admin.vendor.store',
                    ],
                ],
            ],
            [
                'title' => 'Admin Care',
                'routes' => [
                    [
                        'title' => 'Admin List',
                        'route' => 'admin.admins.index',
                    ],
                    [
                        'title' => 'Email All Admins',
                        'route' => 'admin.admins.email.admins',
                    ],
                    [
                        'title' => 'Delete Admin',
                        'route' => 'admin.admins.admin.delete',
                    ],
                    [
                        'title' => 'Send Email',
                        'route' => 'admin.admins.send.email',
                    ],
                    [
                        'title' => 'Search',
                        'route' => 'admin.admins.search',
                    ],
                    [
                        'title' => 'Store',
                        'route' => 'admin.admins.admin.store',
                    ],
                    [
                        'title' => 'Update',
                        'route' => 'admin.admins.admin.update',
                    ],
                    [
                        'title' => 'Status Update',
                        'route' => 'admin.admins.admin.status.update',
                    ],
                ],
            ],
            [
                'title' => 'Role & Permissions',
                'routes' => [
                    [
                        'title' => 'Role List',
                        'route' => 'admin.admins.role.index',
                    ],
                    [
                        'title' => 'Role Store',
                        'route' => 'admin.admins.role.store',
                    ],
                    [
                        'title' => 'Role Update',
                        'route' => 'admin.admins.role.update',
                    ],
                    [
                        'title' => 'Role Delete',
                        'route' => 'admin.admins.role.delete',
                    ],
                    [
                        'title' => 'Permission List',
                        'route' => 'admin.admins.role.permission.index',
                    ],
                    [
                        'title' => 'Permission Create',
                        'route' => 'admin.admins.role.permission.create',
                    ],
                    [
                        'title' => 'Permission Store',
                        'route' => 'admin.admins.role.permission.store',
                    ],
                    [
                        'title' => 'Permission Edit',
                        'route' => 'admin.admins.role.permission.edit',
                    ],
                    [
                        'title' => 'Permission Update',
                        'route' => 'admin.admins.role.permission.update',
                    ],
                    [
                        'title' => 'Permission Delete',
                        'route' => 'admin.admins.role.permission.delete',
                    ],
                    [
                        'title' => 'Permission View',
                        'route' => 'admin.admins.role.permission',
                    ],
                ],
            ],
        ],
    ],
    'settings-permission' => [
        'title' => 'Settings Permissions',
        'sections' => [
            [
                'title'  => 'Web Settings',
                'routes' => [
                    [
                        'title' => 'Basic Settings',
                        'route' => 'admin.web.settings.basic.settings',
                    ],
                    [
                        'title' => 'Basic Settings Update',
                        'route' => 'admin.web.settings.basic.settings.update',
                    ],
                    [
                        'title' => 'Vendor Basic Settings Update',
                        'route' => 'admin.web.settings.vendor.basic.settings.update',
                    ],
                    [
                        'title' => 'Basic Settings Activation Update',
                        'route' => 'admin.web.settings.basic.settings.activation.update',
                    ],
                    [
                        'title' => 'Image Assets',
                        'route' => 'admin.web.settings.image.assets',
                    ],
                    [
                        'title' => 'Vendor Image Assets Update',
                        'route' => 'admin.web.settings.vendor.image.assets.update',
                    ],
                    [
                        'title' => 'Image Assets Update',
                        'route' => 'admin.web.settings.image.assets.update',
                    ],
                    [
                        'title' => 'Setup SEO',
                        'route' => 'admin.web.settings.setup.seo',
                    ],
                    [
                        'title' => 'Setup SEO Update',
                        'route' => 'admin.web.settings.setup.seo.update',
                    ],
                ],
            ],
            [
                'title'  => 'App Settings',
                'routes' => [
                    [
                        'title' => 'Splash Screen',
                        'route' => 'admin.app.settings.splash.screen',
                    ],
                    [
                        'title' => 'Splash Screen Update',
                        'route' => 'admin.app.settings.splash.screen.update',
                    ],
                    [
                        'title' => 'URLs',
                        'route' => 'admin.app.settings.urls',
                    ],
                    [
                        'title' => 'URLs Update',
                        'route' => 'admin.app.settings.urls.update',
                    ],
                    [
                        'title' => 'Vendor Splash Screen',
                        'route' => 'admin.app.settings.vendor.splash.screen',
                    ],
                    [
                        'title' => 'Vendor Splash Screen Update',
                        'route' => 'admin.app.settings.vendor.splash.screen.update',
                    ],
                    [
                        'title' => 'Vendor URLs',
                        'route' => 'admin.app.settings.vendor.urls',
                    ],
                    [
                        'title' => 'Vendor URLs Update',
                        'route' => 'admin.app.settings.vendor.urls.update',
                    ],
                    [
                        'title' => 'Onboard Screens',
                        'route' => 'admin.app.settings.onboard.screens',
                    ],
                    [
                        'title' => 'Onboard Screen Store',
                        'route' => 'admin.app.settings.onboard.screen.store',
                    ],
                    [
                        'title' => 'Onboard Screen Update',
                        'route' => 'admin.app.settings.onboard.screen.update',
                    ],
                    [
                        'title' => 'Onboard Screen Status Update',
                        'route' => 'admin.app.settings.onboard.screen.status.update',
                    ],
                    [
                        'title' => 'Onboard Screen Delete',
                        'route' => 'admin.app.settings.onboard.screen.delete',
                    ],
                    [
                        'title' => 'Vendor Onboard Screens',
                        'route' => 'admin.app.settings.onboard.vendor.screens',
                    ],
                    [
                        'title' => 'Vendor Onboard Screen Store',
                        'route' => 'admin.app.settings.onboard.vendor.screen.store',
                    ],
                    [
                        'title' => 'Vendor Onboard Screen Update',
                        'route' => 'admin.app.settings.onboard.vendor.screen.update',
                    ],
                    [
                        'title' => 'Vendor Onboard Screen Status Update',
                        'route' => 'admin.app.settings.onboard.vendor.screen.status.update',
                    ],
                    [
                        'title' => 'Vendor Onboard Screen Delete',
                        'route' => 'admin.app.settings.onboard.vendor.screen.delete',
                    ],
                ],
            ],
            [
                'title' => 'Setup Email',
                'routes' => [
                    [
                        'title' => 'Email Configuration',
                        'route' => 'admin.setup.email.config',
                    ],
                    [
                        'title' => 'Email Configuration Update',
                        'route' => 'admin.setup.email.config.update',
                    ],
                    [
                        'title' => 'Test Mail Send',
                        'route' => 'admin.setup.email.test.mail.send',
                    ],
                ],
            ],

            [
                'title' => 'Setup Sections',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.setup.sections.section',
                    ],
                    [
                        'title' => 'Update',
                        'route' => 'admin.setup.sections.update',
                    ],
                    [
                        'title' => 'Item Store',
                        'route' => 'admin.setup.sections.item.store',
                    ],
                    [
                        'title' => 'Item Update',
                        'route' => 'admin.setup.sections.item.update',
                    ],
                    [
                        'title' => 'Item Delete',
                        'route' => 'admin.setup.sections.item.delete',
                    ],
                    [
                        'title' => 'Announcement Category Index',
                        'route' => 'admin.setup.sections.announcement.category.index',
                    ],
                    [
                        'title' => 'Announcement Category Create',
                        'route' => 'admin.setup.sections.announcement.category.create',
                    ],
                    [
                        'title' => 'Announcement Category Update',
                        'route' => 'admin.setup.sections.announcement.category.update',
                    ],
                    [
                        'title' => 'Announcement Category Delete',
                        'route' => 'admin.setup.sections.announcement.category.delete',
                    ],
                    [
                        'title' => 'Announcement Category Status Update',
                        'route' => 'admin.setup.sections.announcement.category.status.update',
                    ],
                    [
                        'title' => 'Announcement Index',
                        'route' => 'admin.setup.sections.announcement.index',
                    ],
                    [
                        'title' => 'Announcement Create',
                        'route' => 'admin.setup.sections.announcement.create',
                    ],
                    [
                        'title' => 'Announcement Store',
                        'route' => 'admin.setup.sections.announcement.store',
                    ],
                    [
                        'title' => 'Announcement Edit',
                        'route' => 'admin.setup.sections.announcement.edit',
                    ],
                    [
                        'title' => 'Announcement Update',
                        'route' => 'admin.setup.sections.announcement.update',
                    ],
                    [
                        'title' => 'Announcement Status Update',
                        'route' => 'admin.setup.sections.announcement.status.update',
                    ],
                    [
                        'title' => 'Announcement Delete',
                        'route' => 'admin.setup.sections.announcement.delete',
                    ],
                ],
            ],
            [
                'title' => 'Setup Pages',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.setup.pages.index',
                    ],
                    [
                        'title' => 'Details',
                        'route' => 'admin.setup.pages.details',
                    ],
                    [
                        'title' => 'Update Sections',
                        'route' => 'admin.setup.pages.update.section',
                    ],
                    [
                        'title' => 'Status Update',
                        'route' => 'admin.setup.pages.status.update',
                    ],
                ],
            ],
            [
                'title' => 'Language',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.languages.index',
                    ],
                    [
                        'title' => 'Store',
                        'route' => 'admin.languages.store',
                    ],
                    [
                        'title' => 'Update',
                        'route' => 'admin.languages.update',
                    ],
                    [
                        'title' => 'Status Update',
                        'route' => 'admin.languages.status.update',
                    ],
                    [
                        'title' => 'Info',
                        'route' => 'admin.languages.info',
                    ],
                    [
                        'title' => 'Import',
                        'route' => 'admin.languages.import',
                    ],
                    [
                        'title' => 'Delete',
                        'route' => 'admin.languages.delete',
                    ],
                    [
                        'title' => 'Switch',
                        'route' => 'admin.languages.switch',
                    ],
                    [
                        'title' => 'Download',
                        'route' => 'admin.languages.download',
                    ],
                ],
            ],
            [
                'title'  => 'System Maintenance',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.system.maintenance.index',
                    ],
                    [
                        'title' => 'Update',
                        'route' => 'admin.system.maintenance.update',
                    ],
                ],
            ],
            [
                'title' => 'Extensions',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.extensions.index',
                    ],
                    [
                        'title' => 'Update',
                        'route' => 'admin.extensions.update',
                    ],
                    [
                        'title' => 'Status Update',
                        'route' => 'admin.extensions.status.update',
                    ],
                ],
            ],
            [
                'title' => 'Push Notification',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.push.notification.index',
                    ],
                    [
                        'title' => 'Config',
                        'route' => 'admin.push.notification.config',
                    ],
                    [
                        'title' => 'Update',
                        'route' => 'admin.push.notification.update',
                    ],
                    [
                        'title' => 'Send',
                        'route' => 'admin.push.notification.send',
                    ],
                    [
                        'title' => 'Broadcast Update',
                        'route' => 'admin.push.notification.broadcast.config.update',
                    ],
                ],
            ],
            [
                'title' => 'Useful Links',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.useful.links.index',
                    ],
                    [
                        'title' => 'Store',
                        'route' => 'admin.useful.links.store',
                    ],
                    [
                        'title' => 'Status Update',
                        'route' => 'admin.useful.links.status.update',
                    ],
                    [
                        'title' => 'Edit',
                        'route' => 'admin.useful.links.edit',
                    ],
                    [
                        'title' => 'Update',
                        'route' => 'admin.useful.links.update',
                    ],
                    [
                        'title' => 'Delete',
                        'route' => 'admin.useful.links.delete',
                    ],
                ],
            ],
        ],
    ],
    'transaction-log-permission' => [
        'title' => 'Transaction Logs Permissions',
        'sections' => [
            [
                'title'  => 'Add Money',
                'routes' => [
                    [
                        'title' => 'Add Money Index',
                        'route' => 'admin.add.money.index',
                    ],
                    [
                        'title' => 'Add Money Pending',
                        'route' => 'admin.add.money.pending',
                    ],
                    [
                        'title' => 'Add Money Complete',
                        'route' => 'admin.add.money.complete',
                    ],
                    [
                        'title' => 'Add Money Canceled',
                        'route' => 'admin.add.money.canceled',
                    ],
                    [
                        'title' => 'Add Money Refund',
                        'route' => 'admin.add.money.refund',
                    ],
                    [
                        'title' => 'Add Money Details',
                        'route' => 'admin.add.money.details',
                    ],
                    [
                        'title' => 'Add Money Approved',
                        'route' => 'admin.add.money.approved',
                    ],
                    [
                        'title' => 'Add Money Rejected',
                        'route' => 'admin.add.money.rejected',
                    ],
                    [
                        'title' => 'Add Money Search',
                        'route' => 'admin.add.money.search',
                    ],
                    [
                        'title' => 'Add Money Refund Status',
                        'route' => 'admin.add.money.refund.status',
                    ],
                ],
            ],
            [
                'title'  => 'Money Out Logs',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.money.out.index',
                    ],
                    [
                        'title' => 'Pending',
                        'route' => 'admin.money.out.pending',
                    ],
                    [
                        'title' => 'Complete',
                        'route' => 'admin.money.out.complete',
                    ],
                    [
                        'title' => 'Canceled',
                        'route' => 'admin.money.out.canceled',
                    ],
                    [
                        'title' => 'Approve',
                        'route' => 'admin.money.out.approve',
                    ],
                    [
                        'title' => 'Reject',
                        'route' => 'admin.money.out.reject',
                    ],
                    [
                        'title' => 'Search',
                        'route' => 'admin.money.out.search',
                    ],
                    [
                        'title' => 'Details',
                        'route' => 'admin.money.out.details',
                    ],
                ],
            ],
            [
                'title'  => 'Car Booking Logs',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.booking.index',
                    ],
                    [
                        'title' => 'Pending',
                        'route' => 'admin.booking.pending',
                    ],
                    [
                        'title' => 'Complete',
                        'route' => 'admin.booking.complete',
                    ],
                    [
                        'title' => 'Canceled',
                        'route' => 'admin.booking.canceled',
                    ],
                    [
                        'title' => 'Booking Details',
                        'route' => 'admin.booking.details',
                    ],
                    [
                        'title' => 'Search',
                        'route' => 'admin.booking.search',
                    ],
                ],
            ],
        ],
    ],
    'support-permission' => [
        'title' => 'Support Permissions',
        'sections' => [
            [
                'title' => 'Support Ticket',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.support.ticket.index',
                    ],
                    [
                        'title' => 'Active',
                        'route' => 'admin.support.ticket.active',
                    ],
                    [
                        'title' => 'Pending',
                        'route' => 'admin.support.ticket.pending',
                    ],
                    [
                        'title' => 'Solved',
                        'route' => 'admin.support.ticket.solved',
                    ],
                    [
                        'title' => 'Conversation',
                        'route' => 'admin.support.ticket.conversation',
                    ],
                    [
                        'title' => 'Reply Message',
                        'route' => 'admin.support.ticket.messaage.reply',
                    ],
                    [
                        'title' => 'Solve',
                        'route' => 'admin.support.ticket.solve',
                    ],
                    [
                        'title' => 'Create',
                        'route' => 'admin.support.ticket.create',
                    ],
                    [
                        'title' => 'Store',
                        'route' => 'admin.support.ticket.store',
                    ],
                    [
                        'title' => 'Bulk Delete',
                        'route' => 'admin.support.ticket.bulk.delete',
                    ],
                    [
                        'title' => 'Delete',
                        'route' => 'admin.support.ticket.delete',
                    ],
                ],
            ],
            [
                'title' => 'Contact Message',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.contact.messages.index',
                    ],
                    [
                        'title' => 'Reply',
                        'route' => 'admin.contact.messages.reply',
                    ],
                ],
            ],
            [
                'title' => 'Subscriber',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.subscriber.index',
                    ],
                    [
                        'title' => 'Send Mail',
                        'route' => 'admin.subscriber.send.mail',
                    ],
                ],
            ],
        ],
    ],
    'bonus-permission' => [
        'title' => 'Bonus Permissions',
        'sections' => [
            [
                'title' => 'Server Info',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.server.info.index',
                    ],
                ],
            ],
            [
                'title' => 'Error Logs',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.error.logs.index',
                    ],
                ],
            ],
            [
                'title' => 'Cache',
                'routes' => [
                    [
                        'title' => 'Cache',
                        'route' => 'admin.cache.clear',
                    ],
                ],
            ],
            [
                'title' => 'GDPR Cookie',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.cookie.clear',
                    ],
                    [
                        'title' => 'Update',
                        'route' => 'admin.cookie.update',
                    ],
                ],
            ],
            [
                'title' => 'Twilio Usage',
                'routes' => [
                    [
                        'title' => 'View Usage',
                        'route' => 'admin.twilio.usage.index',
                    ],
                ],
            ],
        ],
    ],
];
