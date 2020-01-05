# ticker-tracker-texter
Track stocks, get texts when you should buy or sell

# Install
- Clone this repo
- Install composer dependencies: `composer install`

# Setup
Set these environment variables:
- `BUY_STOCKS`: a comma-separated list of stock tickers to buy
- `SELL_STOCKS`: a comma-separated list of stock tickers to sell
- `ALPHA_VANTAGE_API_KEY`: a free API key from AlphaVantage.co (to check stocks)
- `TILL_URL`: a [TillMobile integration URL](https://platform.tillmobile.com/api/send?username=xxx&api_key=yyy)
- `RECIPIENT_PHONE`: an 11-digit US phone number (to receive buy/sell messages)

The last two variables are optional; if not set, the results from AlphaVantage
will simply be printed.

# Execution
When everything is set up, simply run `php index.php`.

The script will pick one of the buy or sell stocks at random, check whether the
current price is high (if selling) or low (if buying), and text you if it is a
good time to buy or sell that stock.
