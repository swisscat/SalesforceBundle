# [WORK IN PROGRESS] - SalesforceBundle
(Yet another) Salesforce synchronization tool, using a RabbitMQ engine to defer and bulk synchronization.
Using Streaming API to fetch back records from Salesforce.

# Installation
Require the bundle:
```
composer require swisscat/salesforce-bundle
```
Register the bundle:
```
// app/AppKernel.php

public function registerBundles()
{
    $bundles = [
        // ... ,
        new \OldSound\RabbitMqBundle\OldSoundRabbitMqBundle(),
        new \Swisscat\SalesforceBundle\SalesforceBundle(),
    ];
}
```
[Generate a Salesforce Enterprise WSDL](https://www.google.ch/url?sa=t&rct=j&q=&esrc=s&source=web&cd=2&ved=0ahUKEwj1iIaG4urWAhVLOMAKHbmLB8IQFggtMAE&url=https%3A%2F%2Fdeveloper.salesforce.com%2Fdocs%2Fatlas.en-us.api_meta.meta%2Fapi_meta%2Fmeta_quickstart_get_WSDLs.htm&usg=AOvVaw3b146uriu3vh1Jhv5Gnt4p)

Fill in basic config

```
salesforce:
    soap_client:
        wsdl: "%kernel.root_dir%/config/sf.wsdl"
        username: <username>
        password: <password>
        token: <token>
```

Update schema:
```
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

# Configuration
Edit config.yml
```
salesforce:
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
    consumers:
        salesforce_back:
            connection:       default
            exchange_options: {name: 'salesforce', type: direct}
            queue_options:    {name: 'salesforce'}
            callback:         sylius.consumer.salesforce_back
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

Run the command to fetch Topic Updates (See https://github.com/swisscat/salesforce-amqp-connector)
```
bin/console rabbitmq:consumer salesforce_back
```

# Roadmap

- [ ] Refactor / cleanup
- [ ] Add Tests
- [ ] Improve RabbitMQ exchange configuration
- [ ] Provide queue configurability on ecommerce
- [ ] Handle failures on ecommerce publish
- [ ] Generalize bundle for other providers (i.e magento)
- [ ] Authentication with Token for Java API
- [ ] Implement bundle logic for reconciliation (Master: SF/ECOM/storing conflicts)
- [ ] Improve mapping definition (custom functions)