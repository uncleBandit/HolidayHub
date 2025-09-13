import React from 'react';
import { FaBolt, FaCalendarAlt, FaPercent } from 'react-icons/fa';

const OfferCard = ({ offer }) => {
  if (!offer) return null;

  return (
    <div className="group relative w-full rounded-2xl overflow-hidden shadow-xl transform transition-transform duration-500 hover:shadow-2xl hover:scale-[1.03] cursor-pointer">
      {/* Offer Image and Discount Badge */}
      <div className="relative w-full h-72">
        <img
          src={offer.image}
          alt={offer.title}
          className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
        />
        <div className="absolute top-4 left-4 bg-red-600 text-white font-bold text-lg px-4 py-2 rounded-full shadow-lg transform -rotate-6">
          <FaPercent className="inline-block mr-2" />
          {offer.discount}% OFF
        </div>
      </div>

      {/* Offer Details */}
      <div className="p-6 bg-white">
        <h3 className="font-bold text-xl text-gray-900 mb-2 truncate">{offer.title}</h3>
        <p className="text-gray-600 text-sm mb-4 line-clamp-2">{offer.description}</p>

        <div className="flex justify-between items-center text-sm font-semibold text-gray-500 border-t pt-4">
          <p className="flex items-center">
            <FaCalendarAlt className="text-blue-500 mr-2" />
            Valid until: {offer.valid_until}
          </p>
          <a href="#" className="flex items-center text-blue-600 hover:text-blue-800 transition-colors duration-200">
            Claim Offer
            <FaBolt className="ml-2 text-yellow-500" />
          </a>
        </div>
      </div>
    </div>
  );
};

export default OfferCard;
