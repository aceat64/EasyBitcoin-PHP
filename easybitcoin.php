<?php
/*
EasyBitcoin-PHP

A simple class for making calls to Bitcoin's API using PHP.
https://github.com/aceat64/EasyBitcoin-PHP

====================

The MIT License (MIT)

A permissão é concedida, a título gratuito, a qualquer pessoa que obtenha uma cópia
deste software e arquivos de documentação associados (o "Software"), para lidar
o Software sem restrição, incluindo, sem limitação, os direitos
para usar, copiar, modificar, mesclar, publicar, distribuir, sub-licenciar e / ou vender
cópias do Software, e permitir que as pessoas a quem o Software é
fornecido o façam, sujeito às seguintes condições:

O aviso de copyright acima e este aviso de permissão devem ser incluídos em
todas as cópias ou partes substanciais do Software.

O SOFTWARE É FORNECIDO "COMO ESTÁ", SEM GARANTIA DE QUALQUER TIPO, expressa ou implícita, INCLUINDO, SEM LIMITAÇÃO, AS GARANTIAS DE COMERCIALIZAÇÃO, ADEQUAÇÃO A UM DETERMINADO FIM E NÃO VIOLAÇÃO. EM NENHUM CASO OS AUTORES OU DETENTORES DE DIREITOS AUTORAIS SERÁ RESPONSÁVEL POR QUALQUER RECLAMAÇÃO, DANOS OU OUTRAS RESPONSABILIDADES, SEJA EM UMA AÇÃO DE CONTRATO, DELITO OU OUTRA, DECORRENTE DE,
DE OU EM CONEXÃO COM O SOFTWARE OU O USO OU OUTRA APLICAÇÃO
O SOFTWARE.
====================

// Inicializando Bitcoin conexão/objeto
$bitcoin = new Bitcoin('username','password');

// Opcionalmente, você pode especificar um host e porta.
$bitcoin = new Bitcoin('username','password','host','port');
// Defaults are:
//	host = localhost
//	port = 8332
//	proto = http

// Se você quiser fazer uma conexão SSL é possível definir um certificado CA opcional ou deixe em branco
// Isto irá definir o protocolo para HTTPS e algumas bandeiras CURL
$bitcoin->setSSL('/full/path/to/mycertificate.cert');

// Fazer chamadas para bitcoind como métodos para o seu objeto. As respostas são retornadas como uma matriz.
// Exemplos:
$bitcoin->getinfo();
$bitcoin->getrawtransaction('0e3e2357e806b6cdb1f70b54c3a3a17b6714ee1f0e68bebb44a74b1efd512098',1);
$bitcoin->getblock('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f');

// A resposta completa (são sempre necessárias) é armazenado em $ this-> resposta enquanto o JSON raw é armazenado em $ this-> raw_response

// Quando uma chamada falhar por algum motivo, ele irá retornar FALSE e colocar a mensagem de erro em $ this-> erro
// Exemplo:
echo $bitcoin->error;

// O código de status HTTP pode ser encontrado em $ this-> status e vai ser um código de status HTTP válido ou será 0 se cURL não pôde se conectar.
// Exemplo:
echo $bitcoin->status;

*/

class Bitcoin {
    // Configurando Opções
    private $username;
    private $password;
    private $proto;
    private $host;
    private $port;
    private $url;
    private $CACertificate;

    // Informações e depuração
    public $status;
    public $error;
    public $raw_response;
    public $response;

    private $id = 0;

    /**
     * @param string $username
     * @param string $password
     * @param string $host
     * @param int $port
     * @param string $proto
     * @param string $url
     */
    function __construct($username, $password, $host = 'localhost', $port = 8332, $url = null) {
        $this->username      = $username;
        $this->password      = $password;
        $this->host          = $host;
        $this->port          = $port;
        $this->url           = $url;

        // Definir alguns padrões
        $this->proto         = 'http';
        $this->CACertificate = null;
    }

    /**
     * @param string|null $certificate
     */
    function setSSL($certificate = null) {
        $this->proto         = 'https'; // force HTTPS
        $this->CACertificate = $certificate;
    }

    function __call($method, $params) {
        $this->status       = null;
        $this->error        = null;
        $this->raw_response = null;
        $this->response     = null;

        //Se nenhum parâmetros for passado, este será uma matriz vazia
        $params = array_values($params);

// O ID deve ser exclusivo para cada chamada
        $this->id++;

// Fazendo uma solicitação
        $request = json_encode(array(
            'method' => $method,
            'params' => $params,
            'id'     => $this->id
        ));

        // Construindo uma sessão Curl
        $curl    = curl_init("{$this->proto}://{$this->username}:{$this->password}@{$this->host}:{$this->port}/{$this->url}");
        $options = array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_FOLLOWLOCATION => TRUE,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_HTTPHEADER     => array('Content-type: application/json'),
            CURLOPT_POST           => TRUE,
            CURLOPT_POSTFIELDS     => $request
        );

        // Isso impede que os usuários recebendo o seguinte aviso quando open_basedir está definido:
        // Aviso: curl_setopt () [function.curl-setopt]: CURLOPT_FOLLOWLOCATION não podem ser ativados quando em safe_mode ou um open_basedir é definido
        if (ini_get('open_basedir')) {
            unset($options[CURLOPT_FOLLOWLOCATION]);
        }

        if ($this->proto == 'https') {
        // Se o Certificado CA foi especificado mudamos CURL para ele
            if ($this->CACertificate != null) {
                $options[CURLOPT_CAINFO] = $this->CACertificate;
                $options[CURLOPT_CAPATH] = DIRNAME($this->CACertificate);
            }
            else {
        // Se não precisamos assumir o SSL não pode ser verificada por isso, definir esse sinalizador para FALSE para permitir a conexão
                $options[CURLOPT_SSL_VERIFYPEER] = FALSE;
            }
        }

        curl_setopt_array($curl, $options);

        // Executar o pedido e decodificar a uma matriz
        $this->raw_response = curl_exec($curl);
        $this->response     = json_decode($this->raw_response, TRUE);

        // Se o status não for 200, algo está errado
        $this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Se não houve erro, esta será uma String vazia
        $curl_error = curl_error($curl);

        curl_close($curl);

        if (!empty($curl_error)) {
            $this->error = $curl_error;
        }

        if ($this->response['error']) {
            // Se bitcoind retornou um erro, colocar isso em $ this-> erro
            $this->error = $this->response['error']['message'];
        }
        elseif ($this->status != 200) {
            // Se bitcoind não retornou uma mensagem de erro agradável, precisamos fazer a nossa própria
            switch ($this->status) {
                case 400:
                    $this->error = 'HTTP_BAD_REQUEST';
                    break;
                case 401:
                    $this->error = 'HTTP_UNAUTHORIZED';
                    break;
                case 403:
                    $this->error = 'HTTP_FORBIDDEN';
                    break;
                case 404:
                    $this->error = 'HTTP_NOT_FOUND';
                    break;
            }
        }

        if ($this->error) {
            return FALSE;
        }

        return $this->response['result'];
    }
}
