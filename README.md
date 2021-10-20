Важно
--------


Конфигурирование компонента
---------

Один поток

 ```php
 return [
    'components' => [
        'queue' => [
            'class' => \yii\queue\sync\Queue::class,
            'as group' => [
                'class' => \bigland\queue\chain\behavior\ChainBehavior::class,
                'storage' => \bigland\queue\chain\storage\RedisStorage::class
            ],
        ],
    ],
 ];
 ```

Несколько потоков

 ```php
 return [
    'components' => [
        'queue' => [
            'class' => \yii\queue\sync\Queue::class,
            'as group' => [
                'class' => \bigland\queue\chain\behavior\ChainBehavior::class,
                'storage' => [
                    'class' =>\bigland\queue\chain\storage\RedisStorage::class,
                    'mutex' => [
                        'class' => yii\redis\Mutex::class,
                        'redis' => 'redis',
                        'keyPrefix' => 'your_unique_prefix',
                    ]
                ],
            ],
        ],
    ],
 ];
 ```

Использование
-------------

```php
<?php

namespace \some\namespace;

class GroupJob implements \bigland\queue\chain\job\ChainJobInterface
{
    public string $groupId;


    public function execute($queue)
    {
        //...
        return 'Executed';
    }


    public function finalizeGroup($queue, $results)
    {
        // do final job
    }


    public function setGroupId(string $value): static
    {
        $this->groupId = $value;

        return $this;
    }


    public function getGroupId(): string
    {
        return $this->groupId;
    }
}
````

Запуск задач

```php
$jobs = [
    new \some\namespace\GroupJob([]),
    new \some\namespace\GroupJob([]),
    new \some\namespace\GroupJob([]),
];
            
$pushResult = \Yii::$app->groupQueue->pushGroup($jobs);

$jobIds = $pushResult->jobIds;

$groupId = $pushResult->groupId;
$groupId = (string) $pushResult; // GroupPushedResult implemented \Stringable
```
