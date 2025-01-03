CREATE TABLE wzh_scheduled_content
(
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    content_id          INT          NOT NULL,
    version_no          INT,
    event_date_time     INT          NOT NULL,
    event_action        VARCHAR(255) NOT NULL
);
