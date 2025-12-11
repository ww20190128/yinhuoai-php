SELECT count(*) FROM `questiongroupquestion` WHERE `groupId` in (select `id` from `questiongroup` where `status` =-1) and `status`=0;
update `questiongroupquestion` set `updateTime`=unix_timestamp(now()),`status`=-1 where `groupId` in (select `id` from `questiongroup` where `status` =-1) and `status`=0;


SELECT count(*) FROM `exampaperquestion` WHERE `exampaperId` in (select `id` from `exampaper` where `status` =-1) and `status`=0;
update `exampaperquestion` set `updateTime`=unix_timestamp(now()),`status`=-1 where `exampaperId` in (select `id` from `exampaper` where `status` =-1) and `status`=0;



SELECT count(*) FROM `questiongroupquestion` WHERE `questionId` in (select `id` from `question` where `status` =-1) and `status`=0;
update `questiongroupquestion` set `updateTime`=unix_timestamp(now()),`status`=-1 where `questionId` in (select `id` from `question` where `status` =-1) and `status`=0;


SELECT count(*) FROM `exampaperquestion` WHERE `questionId` in (select `id` from `question` where `status` =-1) and `status`=0;
update `exampaperquestion` set `updateTime`=unix_timestamp(now()),`status`=-1 where `questionId` in (select `id` from `question` where `status` =-1) and `status`=0;