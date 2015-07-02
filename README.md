EasyBitcoin-PHP
===============

Uma classe simples para fazer chamadas para a API do Bitcoin usando PHP.

Começando
---------------
1. Incluir easybitcoin.php em seu script PHP:

`Require_once ('easybitcoin.php'); '
2. Inicializar Bitcoin conexão / object:

`$ Bitcoin = new Bitcoin ('username', 'password'); '

Opcionalmente, você pode especificar um host, a porta. O padrão é HTTP na porta localhost 8332.

`$ Bitcoin = new Bitcoin ('username', 'password', 'localhost' '8332',);`

Se você quiser fazer uma conexão SSL é possível definir um certificado CA opcional ou deixe em branco
`$ Bitcoin-> setSSL ('/ full / path / to / mycertificate.cert'); '

3. Fazer chamadas para bitcoind como métodos para o seu objeto. Exemplos:

`$ Bitcoin-> getinfo ();`
`$ Bitcoin-> getrawtransaction ('0e3e2357e806b6cdb1f70b54c3a3a17b6714ee1f0e68bebb44a74b1efd512098', 1); '
`$ Bitcoin-> getblock ('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f'); '

Informações Adicionais
---------------
* Quando uma chamada falhar por algum motivo, ele irá retornar falso e colocar a mensagem de erro em $ bitcoin-> erro

* O código de status HTTP pode ser encontrado em $ bitcoin-> estado e vai ser um código de status HTTP válido ou será 0 se cURL não pôde se conectar.

* A resposta completa (sem sempre necessárias) é armazenado em $ bitcoin-> resposta enquanto o JSON raw é armazenado em $ bitcoin-> raw_response
