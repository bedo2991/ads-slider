START TRANSACTION;
create table `users`
(
    `id`                bigint unsigned not null auto_increment primary key,
    `name`              varchar(191)    not null,
    `email`             varchar(191)    not null,
    `email_verified_at` timestamp       null,
    `password`          varchar(191)    not null,
    `remember_token`    varchar(100)    null,
    `created_at`        timestamp       null,
    `updated_at`        timestamp       null
) default character set utf8mb4
  collate 'utf8mb4_unicode_ci';
alter table `users`
    add unique `users_email_unique` (`email`);
create table `password_resets`
(
    `email`      varchar(191) not null,
    `token`      varchar(191) not null,
    `created_at` timestamp    null
) default character set utf8mb4
  collate 'utf8mb4_unicode_ci';
alter table `password_resets`
    add index `password_resets_email_index` (`email`);
create table `failed_jobs`
(
    `id`         bigint unsigned                     not null auto_increment primary key,
    `connection` text                                not null,
    `queue`      text                                not null,
    `payload`    longtext                            not null,
    `exception`  longtext                            not null,
    `failed_at`  timestamp default CURRENT_TIMESTAMP not null
) default character set utf8mb4
  collate 'utf8mb4_unicode_ci';
create table `events`
(
    `id`                    bigint unsigned not null auto_increment primary key,
    `name`                  varchar(191)    not null,
    `start`                 date            null,
    `start_time`            time            not null,
    `end`                   date            null,
    `end_time`              time            not null,
    `place`                 varchar(191)    null,
    `icon`                  varchar(191)    null,
    `repeat`                varchar(7)      null,
    `not_closing`           tinyint(1)      not null,
    `final_round_confirmed` tinyint(1)      not null,
    `is_karaoke`            tinyint(1)      not null,
    `disabled`              tinyint(1)      not null,
    `color`                 varchar(7)      not null default '#FFFFFF',
    `user_id`               bigint unsigned not null,
    `created_at`            timestamp       null,
    `updated_at`            timestamp       null
) default character set utf8mb4
  collate 'utf8mb4_unicode_ci';
alter table `events`
    add constraint `events_user_id_foreign` foreign key (`user_id`) references `users` (`id`);
create table `templates`
(
    `id`                    bigint unsigned not null auto_increment primary key,
    `name`                  varchar(191)    not null,
    `start_time`            time            not null,
    `end_time`              time            not null,
    `place`                 varchar(191)    null,
    `icon`                  varchar(191)    null,
    `not_closing`           tinyint(1)      not null,
    `final_round_confirmed` tinyint(1)      not null,
    `is_karaoke`            tinyint(1)      not null,
    `color`                 varchar(7)      not null default '#FFFFFF',
    `user_id`               bigint unsigned not null,
    `created_at`            timestamp       null,
    `updated_at`            timestamp       null
) default character set utf8mb4
  collate 'utf8mb4_unicode_ci';
alter table `templates`
    add constraint `templates_user_id_foreign` foreign key (`user_id`) references `users` (`id`);
create table `pictures`
(
    `id`         bigint unsigned not null auto_increment primary key,
    `name`       varchar(191)    not null,
    `path`       varchar(191)    not null,
    `bg_color`   varchar(7)      not null default '#000000',
    `color`      varchar(7)      not null default '#FFFFFF',
    `user_id`    bigint unsigned not null,
    `created_at` timestamp       null,
    `updated_at` timestamp       null
) default character set utf8mb4
  collate 'utf8mb4_unicode_ci';
alter table `pictures`
    add constraint `pictures_user_id_foreign` foreign key (`user_id`) references `users` (`id`);
create table `picture_slides`
(
    `id`         bigint unsigned not null auto_increment primary key,
    `start`      date            null,
    `start_time` time            not null,
    `end`        date            null,
    `end_time`   time            not null,
    `repeat`     varchar(7)      null,
    `disabled`   tinyint(1)      not null,
    `picture_id` bigint unsigned not null,
    `user_id`    bigint unsigned not null,
    `created_at` timestamp       null,
    `updated_at` timestamp       null
) default character set utf8mb4
  collate 'utf8mb4_unicode_ci';
alter table `picture_slides`
    add constraint `picture_slides_picture_id_foreign` foreign key (`picture_id`) references
        `pictures` (`id`);
alter table `picture_slides`
    add constraint `picture_slides_user_id_foreign` foreign key (`user_id`) references
        `users` (`id`);
create table `menus`
(
    `id`         bigint unsigned not null auto_increment primary key,
    `name`       varchar(191)    not null,
    `path`       varchar(191)    not null,
    `user_id`    bigint unsigned not null,
    `created_at` timestamp       null,
    `updated_at` timestamp       null
) default character set utf8mb4
  collate 'utf8mb4_unicode_ci';
alter table `menus`
    add constraint `menus_user_id_foreign` foreign key (`user_id`) references `users` (`id`);
create table `event_menu`
(
    `id`       bigint unsigned not null auto_increment primary key,
    `event_id` bigint unsigned not null,
    `menu_id`  bigint unsigned not null
) default character set utf8mb4
  collate 'utf8mb4_unicode_ci';
alter table `event_menu`
    add constraint `event_menu_event_id_foreign` foreign key (`event_id`) references `events` (`id`);
alter table `event_menu`
    add constraint `event_menu_menu_id_foreign` foreign key (`menu_id`) references `menus` (`id`);
create table `monitors`
(
    `id`                            bigint unsigned not null auto_increment primary key,
    `name`                          varchar(50)     not null,
    `events_to_show`                int             not null default '8',
    `show_preparation_countdowns`   tinyint(1)      not null,
    `show_final_rounds`             tinyint(1)      not null,
    `show_we_are_closing`           tinyint(1)      not null,
    `show_we_are_closed_marketing`  tinyint(1)      not null,
    `show_cancelled_events`         tinyint(1)      not null,
    `show_menus`                    tinyint(1)      not null,
    `show_happy_hours`              tinyint(1)      not null,
    `show_pictures`                 tinyint(1)      not null,
    `show_karaoke`                  tinyint(1)      not null,
    `show_weather_forecast`         tinyint(1)      not null,
    `use_animations`                tinyint(1)      not null,
    `show_marquee`                  tinyint(1)      not null,
    `show_event_while_is_happening` tinyint(1)      not null,
    `api_token`                     varchar(80)     null,
    `user_id`                       bigint unsigned not null,
    `created_at`                    timestamp       null,
    `updated_at`                    timestamp       null
) default character set utf8mb4
  collate
      'utf8mb4_unicode_ci';
alter table `monitors`
    add constraint `monitors_user_id_foreign` foreign key (`user_id`) references `users` (`id`);
alter table `monitors`
    add unique `monitors_api_token_unique` (`api_token`);
alter table `events`
    add `cancelled` tinyint(1) not null default '0';
create table `menu_monitor`
(
    `id`         bigint unsigned not null auto_increment primary key,
    `menu_id`    bigint unsigned not
                                     null,
    `monitor_id` bigint unsigned not null
) default character set utf8mb4
  collate 'utf8mb4_unicode_ci';
alter table `menu_monitor`
    add constraint `menu_monitor_monitor_id_foreign` foreign key (`monitor_id`) references
        `monitors` (`id`);
alter table `menu_monitor`
    add constraint `menu_monitor_menu_id_foreign` foreign key (`menu_id`) references `menus` (`id`);
create table `monitor_picture`
(
    `id`         bigint unsigned not null auto_increment primary key,
    `monitor_id` bigint
                     unsigned    not null,
    `picture_id` bigint unsigned not null
) default character set utf8mb4
  collate 'utf8mb4_unicode_ci';
alter table `monitor_picture`
    add constraint `monitor_picture_monitor_id_foreign` foreign key (`monitor_id`)
        references `monitors` (`id`);
alter table `monitor_picture`
    add constraint `monitor_picture_picture_id_foreign` foreign key (`picture_id`)
        references `pictures` (`id`);
create table `menu_template`
(
    `id`          bigint unsigned not null auto_increment primary key,
    `template_id` bigint unsigned not null,
    `menu_id`      bigint unsigned not null
) default character set utf8mb4
  collate 'utf8mb4_unicode_ci';
alter table `menu_template`
    add constraint `menu_template_template_id_foreign` foreign key (`template_id`) references
        `templates` (`id`);
alter table `menu_template`
    add constraint `menu_template_menu_id_foreign` foreign key (`menu_id`) references `menus`
        (`id`);

commit;
