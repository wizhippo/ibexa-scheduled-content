CREATE TABLE wzh_scheduled_content
(
    id              SERIAL PRIMARY KEY,
    content_id      INTEGER NOT NULL,
    event_date_time INTEGER NOT NULL,
    event_action    VARCHAR NOT NULL,
    remark          VARCHAR,
    evaluated_date_time       INTEGER
);
