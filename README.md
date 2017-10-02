# SalesforceMapperBundle
(Yet another) salesforce synchronization tool, using a RabbitMQ engine to defer and bulk synchronization.

# Configuration
Edit config.yml
```
phpforce_salesforce:
    soap_client:
        wsdl: "%kernel.root_dir%/config/sf.wsdl"
        username: <username>
        password: <password>
        token: <token>

old_sound_rabbit_mq:
    connections:
        default:
            host:     'localhost'
            port:     5672
            user:     'guest'
            password: 'guest'
            vhost:    '/'
            lazy:     true
            connection_timeout: 60
            keepalive: true
            use_socket: false # default false
    producers:
        sync_salesforce:
            connection:       default
            exchange_options: {name: 'sync-salesforce', type: direct}
            service_alias:    sync_salesforce # no alias by default
    batch_consumers:
        sync_salesforce:
            connection:       default
            exchange_options: {name: 'sync-salesforce', type: direct}
            queue_options:    {name: 'sync-salesforce'}
            callback:         sylius.consumer.salesforce
            qos_options:      {prefetch_size: 0, prefetch_count: 2, global: false}
```

Run the command to process records
```
bin/console rabbitmq:batch:consumer sync_salesforce
```