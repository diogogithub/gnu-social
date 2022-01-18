FROM node

RUN apt-get update && apt-get install -y \
    apt-transport-https ca-certificates curl fontconfig \
    fonts-ipafont-gothic fonts-kacst fonts-liberation fonts-thai-tlwg \
    fonts-wqy-zenhei gconf-service libgbm-dev libasound2 \
    libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 \
    libfontconfig1 libgcc1 libgconf-2-4 libgdk-pixbuf2.0-0 libglib2.0-0 \
    libgtk-3-0 libnspr4 libnss3 libpango-1.0-0 libpangocairo-1.0-0 \
    libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 \
    libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 \
    libxtst6 locales lsb-release unzip xdg-utils wget imagemagick php bc \
    && apt-get clean \
    && apt-get autoremove -q \
    && npm install -g pa11y-ci

RUN useradd -ms /bin/bash puppet
