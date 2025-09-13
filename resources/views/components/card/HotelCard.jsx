import React from 'react';
import { FaStar, FaHeart, FaMapMarkerAlt } from 'react-icons/fa';

const HotelCard = ({ hotel }) => {
  if (!hotel) return null;

  return (
    <div className="group relative bg-white rounded-2xl shadow-xl overflow-hidden transform transition-all duration-300 hover:shadow-2xl hover:scale-[1.02]">
      {/* Hotel Image and Favorite Button */}
      <div className="relative w-full h-64 overflow-hidden">
        <img
          src={hotel.cover_image}
          alt={hotel.name}
          className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
        />
        <div className="absolute top-4 right-4 bg-white/70 backdrop-blur-sm p-2 rounded-full cursor-pointer hover:bg-white transition-colors duration-300">
          <FaHeart className="text-red-500 text-lg" />
        </div>
      </div>

      {/* Hotel Details */}
      <div className="p-6">
        <div className="flex justify-between items-center mb-2">
          {/* Hotel Name and Location */}
          <div>
            <h3 className="font-bold text-xl text-gray-900 truncate">{hotel.name}</h3>
            <p className="flex items-center text-sm text-gray-500 mt-1">
              <FaMapMarkerAlt className="text-xs text-gray-400 mr-1" />
              {hotel.city}, {hotel.country}
            </p>
          </div>

          {/* Rating */}
          <div className="flex items-center bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full">
            <FaStar className="text-yellow-300 text-sm mr-1" />
            <span>{hotel.rating}</span>
          </div>
        </div>

        {/* Price and Price Range */}
        <div className="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center">
          <div className="text-lg font-semibold text-gray-800">
            {hotel.price_range === 'Luxury' && '$$$$'}
            {hotel.price_range === 'Mid-Range' && '$$$'}
            {hotel.price_range === 'Budget' && '$$'}
          </div>
          <p className="text-sm font-medium text-blue-600">
            View Details &rarr;
          </p>
        </div>
      </div>
    </div>
  );
};

export default HotelCard;
