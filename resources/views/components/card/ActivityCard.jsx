import React from 'react';
import { FaMapMarkerAlt, FaTag, FaArrowRight } from 'react-icons/fa';

const ActivityCard = ({ activity }) => {
  if (!activity) return null;

  return (
    <div className="group relative w-full rounded-2xl overflow-hidden shadow-xl transform transition-transform duration-500 hover:shadow-2xl hover:scale-[1.03] cursor-pointer">
      {/* Background Image */}
      <div className="relative w-full h-64 overflow-hidden">
        <img
          src={activity.cover_image}
          alt={activity.name}
          className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
      </div>

      {/* Content */}
      <div className="absolute bottom-0 left-0 p-6 w-full text-white">
        <h3 className="font-bold text-2xl mb-1 drop-shadow-md">{activity.name}</h3>
        <p className="flex items-center text-sm font-light text-gray-200 mb-2">
          <FaMapMarkerAlt className="mr-2" />
          {activity.location}
        </p>

        <div className="flex items-center justify-between mt-2 pt-2 border-t border-gray-400/30">
          {/* Price Tag */}
          <div className="flex items-center text-lg font-semibold bg-white text-gray-800 rounded-full px-4 py-1 shadow-lg">
            <FaTag className="mr-2 text-blue-500" />
            <span className="text-blue-600">${activity.price}</span>
          </div>

          {/* Call to Action */}
          <button className="flex items-center text-sm font-medium text-blue-400 group-hover:text-blue-300 transition-colors duration-200">
            Book Now <FaArrowRight className="ml-2" />
          </button>
        </div>
      </div>
    </div>
  );
};

export default ActivityCard;
