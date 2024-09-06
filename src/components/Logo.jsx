import React from 'react';

const Logo = ({ color = 'black', text = 'Your Logo' }) => {
    return <h1 style={{ color, fontFamily: 'Arial, sans-serif', fontSize: '24px' }}>{text}</h1>;
};

export default Logo;