<?php

/**
 * Class Rates
 */
class Rates{

	private $date = '';
    private $url  = 'http://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?';
	private $printingText = '';
    private $valcode = 'USD';
    private $response = [];
    private $preDate = '';
    private $yesterdayRate = [];

	/**
	 * Rates constructor.
     * @var string $date
	 */
	public function __construct($date)
	{      $date = strtotime($date);

		if (!$date){
			$this->date = $this->getDateForAPI();
            $this->preDate = date('Ymd', strtotime('-1day', strtotime($this->date)));
		}else{
			$this->date = $date;
            $this->preDate = date('Ymd', strtotime('-1day', strtotime($this->date)));
		}
		$this->printingText = $this->validate();

	}


	private function getDateForAPI()
	{
		$weekDay = date('N');
		switch ($weekDay) {
			case 7:
				return date('Ymd', strtotime('-2day'));
				break;
			case 6:
				return date('Ymd', strtotime('-1day'));
				break;
			default:
				return date('Ymd');
				break;
		}

	}


	private function validate(){
		$arguments = $_SERVER['argv'];
		$flag = @$arguments[1];
		switch ($flag){
			case('--code'):
				return 'r030';
			case('--full'):
				return 'txt';
			default:
				return 'cc';
		}


	}

	public function getData(){
	    try{
            $result = (new Curl(['url'=>$this->url, 'date'=> $this->date, 'valcode'=>$this->valcode,]))->get();
        }catch (InvalidArgumentException $ex){
            $result = [];
        }
        $r = json_decode($result, true);
        $r = $r[0];
        $this->response = $r;
        try{
            $result = (new Curl(['url'=>$this->url, 'date'=> $this->preDate, 'valcode'=>$this->valcode,]))->get();
        }catch (InvalidArgumentException $ex){
            $result = [];
        }
        $yR =   json_decode($result, true);
        $yR = $yR[0];
        $this->yesterdayRate = $yR;

        return $this;
    }

    public function printing(){
        if (null!==$this->response):
            $output  = $this->response[$this->printingText];
            $output .= ': ';
            $output .= round($this->response['rate'], 2);
            $output .= ' UAH';
            $output .= ($this->response['rate'] > $this->yesterdayRate['rate'])?'↑':'↓';
            $output .= '('.round($this->yesterdayRate['rate'], 2).')';
            $output .= PHP_EOL;
        else:
            $output = 'Oops, no rates';
        endif;
        echo $output;
    }

}

class Curl{

    private $url    = '';
    private $format = '&json';
    private $date   = '';
    private $valcode = '';

    /**
     * Curl constructor.
     * ['url'=>,'date'=> , 'valcode'=>,]
     * @var array $params.
     * @throws InvalidArgumentException;
     */
    public function __construct($params = [])
    {
        if (null !== $params && count($params)!==0 ){
            $this->date     .= $params['date'];
            $this->valcode  .= $params['valcode'];
            $this->url       = $params['url'];
        }else{
            throw new InvalidArgumentException('Params must be set.', 400);
        }
    }

    public function get(){
        $requestString = $this->url.http_build_query(['valcode' =>  $this->valcode,
                                                        'date'  =>  $this->date,]
                                                        ).$this->format;
		$chan = curl_init($requestString);
        curl_setopt($chan, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($chan);
        curl_close($chan);
        return $result;

    }
}

$r = (new Rates(''))->getData()->printing();


