<?php

class apiController extends jController
{
    protected $error_codes = array(
        'error' => 0,
        'success' => 1,
    );

    protected $http_codes = array(
        '200' => 'Successfull operation',
        '401' => 'Unauthorize',
        '400' => 'Bad Request',
        '403' => 'Forbidden',
        '404' => 'Not found',
        '405' => 'Method Not Allowed',
        '500' => 'Internal Server Error',
    );

    protected $user;

    protected $requestSyncDate = null;

    protected $lastSyncDate = null;

    /**
     * Authenticate the user via JWC token
     * Token is given in Authorization header as: Authorization: Bearer <token>.
     */
    protected function authenticate()
    {

        // Get token tool
        jClasses::inc('gobsapi~Token');
        $token_manager = new Token();

        // Get request token
        $token = $token_manager->getTokenFromHeader();
        if (!$token) {
            return false;
        }

        // Validate token
        $user = $token_manager->getUserFromToken($token);
        if (!$user) {
            return false;
        }

        // Add user in property
        $this->user = $user;

        // Add requestSyncDate & lastSyncDate
        $headers = jApp::coord()->request->headers();
        $sync_dates = array(
            'Requestsyncdate'=> 'requestSyncDate',
            'Lastsyncdate'=>'lastSyncDate'
        );
        foreach($sync_dates as $key=>$prop) {
            if (array_key_exists($key, $headers)) {
                $sync_date = $headers[$key];
                if ($this->isValidDate($sync_date)) {
                    $this->$prop = $sync_date;
                }
            }
        }

        return true;
    }

    /**
     * Validate a string containing date
     * @param string date String to validate. Ex: "2020-12-12 08:34:45"
     * @param string format Format of the date to validate against. Default "Y-m-d H:i:s"
     *
     * @return boolean
     */
    private function isValidDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    /**
     * Return api response in JSON format
     * E.g. {"code": 0, "status": "error", "message":  "Method Not Allowed"}.
     *
     * @param string http_code HTTP status code. Ex: 200
     * @param string status 'error' or 'success'
     * @param string message Message with response content
     * @param mixed      $http_code
     * @param null|mixed $status
     * @param null|mixed $message
     * @httpresponse JSON with code, status and message
     *
     * @return jResponseJson
     */
    protected function apiResponse($http_code = '200', $status = null, $message = null)
    {
        $rep = $this->getResponse('json');
        $rep->setHttpStatus($http_code, $this->http_codes[$http_code]);

        if ($status) {
            $rep->data = array(
                'code' => $this->error_codes[$status],
                'status' => $status,
                'message' => $message,
            );
        }

        return $rep;
    }

    /**
     * Return object(s) in JSON format.
     *
     * @param array data Array containing the  projects
     * @param mixed $data
     * @httpresponse JSON with project data
     *
     * @return jResponseJson
     */
    protected function objectResponse($data)
    {
        $rep = $this->getResponse('json');
        $http_code = '200';
        $rep->setHttpStatus($http_code, $this->http_codes[$http_code]);
        $rep->data = $data;

        return $rep;
    }
}
